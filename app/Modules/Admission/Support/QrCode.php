<?php

namespace Modules\Admission\Support;

use RuntimeException;

/**
 * Generator QR Code (mode byte, versi 1–40, ECC L/M/Q/H) — porting PHP dari
 * "QR Code generator library" Project Nayuki (MIT), versi yang sama yang
 * dibundel `qrcode.react` di frontend, sehingga keluaran identik (diuji di
 * Tests/Unit/QrCodeTest). Dipakai untuk QR kartu peserta & LoA di PDF tanpa
 * menambah dependensi Composer.
 */
final class QrCode
{
    /** Urutan ordinal sama dengan pustaka asli: L, M, Q, H → formatBits. */
    private const ECC_FORMAT_BITS = ['L' => 1, 'M' => 0, 'Q' => 3, 'H' => 2];

    private const ECC_ORDINAL = ['L' => 0, 'M' => 1, 'Q' => 2, 'H' => 3];

    private const ECC_CODEWORDS_PER_BLOCK = [
        [-1, 7, 10, 15, 20, 26, 18, 20, 24, 30, 18, 20, 24, 26, 30, 22, 24, 28, 30, 28, 28, 28, 28, 30, 30, 26, 28, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30],
        [-1, 10, 16, 26, 18, 24, 16, 18, 22, 22, 26, 30, 22, 22, 24, 24, 28, 28, 26, 26, 26, 26, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28],
        [-1, 13, 22, 18, 26, 18, 24, 18, 22, 20, 24, 28, 26, 24, 20, 30, 24, 28, 28, 26, 30, 28, 30, 30, 30, 30, 28, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30],
        [-1, 17, 28, 22, 16, 22, 28, 26, 26, 24, 28, 24, 28, 22, 24, 24, 30, 28, 28, 26, 28, 30, 24, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30],
    ];

    private const NUM_ERROR_CORRECTION_BLOCKS = [
        [-1, 1, 1, 1, 1, 1, 2, 2, 2, 2, 4, 4, 4, 4, 4, 6, 6, 6, 6, 7, 8, 8, 9, 9, 10, 12, 12, 12, 13, 14, 15, 16, 17, 18, 19, 19, 20, 21, 22, 24, 25],
        [-1, 1, 1, 1, 2, 2, 4, 4, 4, 5, 5, 5, 8, 9, 9, 10, 10, 11, 13, 14, 16, 17, 17, 18, 20, 21, 23, 25, 26, 28, 29, 31, 33, 35, 37, 38, 40, 43, 45, 47, 49],
        [-1, 1, 1, 2, 2, 4, 4, 6, 6, 8, 8, 8, 10, 12, 16, 12, 17, 16, 18, 21, 20, 23, 23, 25, 27, 29, 34, 34, 35, 38, 40, 43, 45, 48, 51, 53, 56, 59, 62, 65, 68],
        [-1, 1, 1, 2, 4, 4, 4, 5, 6, 8, 8, 11, 11, 16, 16, 18, 16, 19, 21, 25, 25, 25, 34, 30, 32, 35, 37, 40, 42, 45, 48, 51, 54, 57, 60, 63, 66, 70, 74, 77, 81],
    ];

    private int $size;

    /** @var array<int, array<int, bool>> */
    private array $modules = [];

    /** @var array<int, array<int, bool>> */
    private array $isFunction = [];

    private function __construct(private readonly int $version, private readonly string $ecl, array $dataCodewords)
    {
        $this->size = $version * 4 + 17;
        $row = array_fill(0, $this->size, false);
        $this->modules = array_fill(0, $this->size, $row);
        $this->isFunction = array_fill(0, $this->size, $row);

        $this->drawFunctionPatterns();
        $this->drawCodewords($this->addEccAndInterleave($dataCodewords));

        $mask = 0;
        $minPenalty = PHP_INT_MAX;

        for ($i = 0; $i < 8; $i++) {
            $this->applyMask($i);
            $this->drawFormatBits($i);
            $penalty = $this->penaltyScore();

            if ($penalty < $minPenalty) {
                $mask = $i;
                $minPenalty = $penalty;
            }

            $this->applyMask($i);
        }

        $this->applyMask($mask);
        $this->drawFormatBits($mask);
    }

    /**
     * Matriks modul (baris → kolom) untuk teks UTF-8 dalam mode byte.
     *
     * @return array<int, array<int, bool>>
     */
    public static function matrix(string $text, string $ecl = 'M'): array
    {
        return self::encode($text, $ecl)->modules;
    }

    /** PNG data URI (GD) untuk disematkan di PDF dompdf. */
    public static function pngDataUri(string $text, int $scale = 6, int $margin = 4, string $ecl = 'M'): string
    {
        $matrix = self::matrix($text, $ecl);
        $size = count($matrix);
        $pixels = ($size + $margin * 2) * $scale;
        $image = imagecreatetruecolor($pixels, $pixels);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $white);

        foreach ($matrix as $y => $row) {
            foreach ($row as $x => $dark) {
                if ($dark) {
                    $x0 = ($x + $margin) * $scale;
                    $y0 = ($y + $margin) * $scale;
                    imagefilledrectangle($image, $x0, $y0, $x0 + $scale - 1, $y0 + $scale - 1, $black);
                }
            }
        }

        ob_start();
        imagepng($image);
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode((string) ob_get_clean());
    }

    private static function encode(string $text, string $ecl): self
    {
        $bytes = array_values(unpack('C*', $text) ?: []);
        $ordinal = self::ECC_ORDINAL[$ecl] ?? throw new RuntimeException('ECC tidak dikenal.');

        for ($version = 1; ; $version++) {
            $capacityBits = self::numDataCodewords($version, $ordinal) * 8;
            $usedBits = 4 + ($version < 10 ? 8 : 16) + count($bytes) * 8;

            if ($usedBits <= $capacityBits) {
                break;
            }

            if ($version >= 40) {
                throw new RuntimeException('Data terlalu panjang untuk QR.');
            }
        }

        // Naikkan ECC bila masih muat (boostEcl, perilaku pustaka asli).
        foreach (['M', 'Q', 'H'] as $candidate) {
            if ($usedBits <= self::numDataCodewords($version, self::ECC_ORDINAL[$candidate]) * 8) {
                $ecl = $candidate;
            }
        }

        $ordinal = self::ECC_ORDINAL[$ecl];
        $bits = [];
        self::appendBits(0x4, 4, $bits);
        self::appendBits(count($bytes), $version < 10 ? 8 : 16, $bits);

        foreach ($bytes as $byte) {
            self::appendBits($byte, 8, $bits);
        }

        $capacityBits = self::numDataCodewords($version, $ordinal) * 8;
        self::appendBits(0, min(4, $capacityBits - count($bits)), $bits);
        self::appendBits(0, (8 - count($bits) % 8) % 8, $bits);

        for ($pad = 0xEC; count($bits) < $capacityBits; $pad ^= 0xEC ^ 0x11) {
            self::appendBits($pad, 8, $bits);
        }

        $codewords = array_fill(0, intdiv(count($bits), 8), 0);

        foreach ($bits as $i => $bit) {
            $codewords[$i >> 3] |= $bit << (7 - ($i & 7));
        }

        return new self($version, $ecl, $codewords);
    }

    private function drawFunctionPatterns(): void
    {
        for ($i = 0; $i < $this->size; $i++) {
            $this->setFunction(6, $i, $i % 2 === 0);
            $this->setFunction($i, 6, $i % 2 === 0);
        }

        $this->drawFinder(3, 3);
        $this->drawFinder($this->size - 4, 3);
        $this->drawFinder(3, $this->size - 4);

        $positions = $this->alignmentPositions();
        $count = count($positions);

        for ($i = 0; $i < $count; $i++) {
            for ($j = 0; $j < $count; $j++) {
                if (! (($i === 0 && $j === 0) || ($i === 0 && $j === $count - 1) || ($i === $count - 1 && $j === 0))) {
                    $this->drawAlignment($positions[$i], $positions[$j]);
                }
            }
        }

        $this->drawFormatBits(0);
        $this->drawVersion();
    }

    private function drawFormatBits(int $mask): void
    {
        $data = (self::ECC_FORMAT_BITS[$this->ecl] << 3) | $mask;
        $rem = $data;

        for ($i = 0; $i < 10; $i++) {
            $rem = ($rem << 1) ^ (($rem >> 9) * 0x537);
        }

        $bits = (($data << 10) | $rem) ^ 0x5412;

        for ($i = 0; $i <= 5; $i++) {
            $this->setFunction(8, $i, self::bit($bits, $i));
        }

        $this->setFunction(8, 7, self::bit($bits, 6));
        $this->setFunction(8, 8, self::bit($bits, 7));
        $this->setFunction(7, 8, self::bit($bits, 8));

        for ($i = 9; $i < 15; $i++) {
            $this->setFunction(14 - $i, 8, self::bit($bits, $i));
        }

        for ($i = 0; $i < 8; $i++) {
            $this->setFunction($this->size - 1 - $i, 8, self::bit($bits, $i));
        }

        for ($i = 8; $i < 15; $i++) {
            $this->setFunction(8, $this->size - 15 + $i, self::bit($bits, $i));
        }

        $this->setFunction(8, $this->size - 8, true);
    }

    private function drawVersion(): void
    {
        if ($this->version < 7) {
            return;
        }

        $rem = $this->version;

        for ($i = 0; $i < 12; $i++) {
            $rem = ($rem << 1) ^ (($rem >> 11) * 0x1F25);
        }

        $bits = ($this->version << 12) | $rem;

        for ($i = 0; $i < 18; $i++) {
            $color = self::bit($bits, $i);
            $a = $this->size - 11 + $i % 3;
            $b = intdiv($i, 3);
            $this->setFunction($a, $b, $color);
            $this->setFunction($b, $a, $color);
        }
    }

    private function drawFinder(int $x, int $y): void
    {
        for ($dy = -4; $dy <= 4; $dy++) {
            for ($dx = -4; $dx <= 4; $dx++) {
                $dist = max(abs($dx), abs($dy));
                $xx = $x + $dx;
                $yy = $y + $dy;

                if ($xx >= 0 && $xx < $this->size && $yy >= 0 && $yy < $this->size) {
                    $this->setFunction($xx, $yy, $dist !== 2 && $dist !== 4);
                }
            }
        }
    }

    private function drawAlignment(int $x, int $y): void
    {
        for ($dy = -2; $dy <= 2; $dy++) {
            for ($dx = -2; $dx <= 2; $dx++) {
                $this->setFunction($x + $dx, $y + $dy, max(abs($dx), abs($dy)) !== 1);
            }
        }
    }

    private function setFunction(int $x, int $y, bool $dark): void
    {
        $this->modules[$y][$x] = $dark;
        $this->isFunction[$y][$x] = true;
    }

    /**
     * @param  array<int, int>  $data
     * @return array<int, int>
     */
    private function addEccAndInterleave(array $data): array
    {
        $ordinal = self::ECC_ORDINAL[$this->ecl];
        $numBlocks = self::NUM_ERROR_CORRECTION_BLOCKS[$ordinal][$this->version];
        $blockEccLen = self::ECC_CODEWORDS_PER_BLOCK[$ordinal][$this->version];
        $rawCodewords = intdiv(self::numRawDataModules($this->version), 8);
        $numShortBlocks = $numBlocks - $rawCodewords % $numBlocks;
        $shortBlockLen = intdiv($rawCodewords, $numBlocks);
        $divisor = self::rsDivisor($blockEccLen);
        $blocks = [];

        for ($i = 0, $k = 0; $i < $numBlocks; $i++) {
            $length = $shortBlockLen - $blockEccLen + ($i < $numShortBlocks ? 0 : 1);
            $dat = array_slice($data, $k, $length);
            $k += count($dat);
            $ecc = self::rsRemainder($dat, $divisor);

            if ($i < $numShortBlocks) {
                $dat[] = 0;
            }

            $blocks[] = array_merge($dat, $ecc);
        }

        $result = [];
        $blockLength = count($blocks[0]);

        for ($i = 0; $i < $blockLength; $i++) {
            foreach ($blocks as $j => $block) {
                if ($i !== $shortBlockLen - $blockEccLen || $j >= $numShortBlocks) {
                    $result[] = $block[$i];
                }
            }
        }

        return $result;
    }

    /** @param  array<int, int>  $data */
    private function drawCodewords(array $data): void
    {
        $i = 0;
        $total = count($data) * 8;

        for ($right = $this->size - 1; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right = 5;
            }

            for ($vert = 0; $vert < $this->size; $vert++) {
                for ($j = 0; $j < 2; $j++) {
                    $x = $right - $j;
                    $upward = (($right + 1) & 2) === 0;
                    $y = $upward ? $this->size - 1 - $vert : $vert;

                    if (! $this->isFunction[$y][$x] && $i < $total) {
                        $this->modules[$y][$x] = self::bit($data[$i >> 3], 7 - ($i & 7));
                        $i++;
                    }
                }
            }
        }
    }

    private function applyMask(int $mask): void
    {
        for ($y = 0; $y < $this->size; $y++) {
            for ($x = 0; $x < $this->size; $x++) {
                $invert = match ($mask) {
                    0 => ($x + $y) % 2 === 0,
                    1 => $y % 2 === 0,
                    2 => $x % 3 === 0,
                    3 => ($x + $y) % 3 === 0,
                    4 => (intdiv($x, 3) + intdiv($y, 2)) % 2 === 0,
                    5 => ($x * $y % 2 + $x * $y % 3) === 0,
                    6 => (($x * $y % 2 + $x * $y % 3) % 2) === 0,
                    7 => ((($x + $y) % 2 + $x * $y % 3) % 2) === 0,
                };

                if (! $this->isFunction[$y][$x] && $invert) {
                    $this->modules[$y][$x] = ! $this->modules[$y][$x];
                }
            }
        }
    }

    private function penaltyScore(): int
    {
        $result = 0;

        for ($axis = 0; $axis < 2; $axis++) {
            for ($a = 0; $a < $this->size; $a++) {
                $runColor = false;
                $run = 0;
                $history = [0, 0, 0, 0, 0, 0, 0];

                for ($b = 0; $b < $this->size; $b++) {
                    $color = $axis === 0 ? $this->modules[$a][$b] : $this->modules[$b][$a];

                    if ($color === $runColor) {
                        $run++;

                        if ($run === 5) {
                            $result += 3;
                        } elseif ($run > 5) {
                            $result++;
                        }
                    } else {
                        $this->addHistory($run, $history);

                        if (! $runColor) {
                            $result += $this->countPatterns($history) * 40;
                        }

                        $runColor = $color;
                        $run = 1;
                    }
                }

                $result += $this->terminateAndCount($runColor, $run, $history) * 40;
            }
        }

        for ($y = 0; $y < $this->size - 1; $y++) {
            for ($x = 0; $x < $this->size - 1; $x++) {
                $color = $this->modules[$y][$x];

                if ($color === $this->modules[$y][$x + 1] && $color === $this->modules[$y + 1][$x] && $color === $this->modules[$y + 1][$x + 1]) {
                    $result += 3;
                }
            }
        }

        $dark = 0;

        foreach ($this->modules as $row) {
            foreach ($row as $module) {
                $dark += $module ? 1 : 0;
            }
        }

        $total = $this->size * $this->size;
        $k = (int) ceil(abs($dark * 20 - $total * 10) / $total) - 1;

        return $result + $k * 10;
    }

    /** @param  array<int, int>  $history */
    private function countPatterns(array $history): int
    {
        $n = $history[1];
        $core = $n > 0 && $history[2] === $n && $history[3] === $n * 3 && $history[4] === $n && $history[5] === $n;

        return ($core && $history[0] >= $n * 4 && $history[6] >= $n ? 1 : 0) + ($core && $history[6] >= $n * 4 && $history[0] >= $n ? 1 : 0);
    }

    /** @param  array<int, int>  $history */
    private function terminateAndCount(bool $color, int $run, array &$history): int
    {
        if ($color) {
            $this->addHistory($run, $history);
            $run = 0;
        }

        $run += $this->size;
        $this->addHistory($run, $history);

        return $this->countPatterns($history);
    }

    /** @param  array<int, int>  $history */
    private function addHistory(int $run, array &$history): void
    {
        if ($history[0] === 0) {
            $run += $this->size;
        }

        array_pop($history);
        array_unshift($history, $run);
    }

    /** @return array<int, int> */
    private function alignmentPositions(): array
    {
        if ($this->version === 1) {
            return [];
        }

        $numAlign = intdiv($this->version, 7) + 2;
        $step = $this->version === 32 ? 26 : (int) ceil(($this->version * 4 + 4) / ($numAlign * 2 - 2)) * 2;
        $result = [6];

        for ($pos = $this->size - 7; count($result) < $numAlign; $pos -= $step) {
            array_splice($result, 1, 0, [$pos]);
        }

        return $result;
    }

    private static function numRawDataModules(int $version): int
    {
        $result = (16 * $version + 128) * $version + 64;

        if ($version >= 2) {
            $numAlign = intdiv($version, 7) + 2;
            $result -= (25 * $numAlign - 10) * $numAlign - 55;

            if ($version >= 7) {
                $result -= 36;
            }
        }

        return $result;
    }

    private static function numDataCodewords(int $version, int $ordinal): int
    {
        return intdiv(self::numRawDataModules($version), 8) - self::ECC_CODEWORDS_PER_BLOCK[$ordinal][$version] * self::NUM_ERROR_CORRECTION_BLOCKS[$ordinal][$version];
    }

    /** @return array<int, int> */
    private static function rsDivisor(int $degree): array
    {
        $result = array_fill(0, $degree - 1, 0);
        $result[] = 1;
        $root = 1;

        for ($i = 0; $i < $degree; $i++) {
            for ($j = 0; $j < count($result); $j++) {
                $result[$j] = self::rsMultiply($result[$j], $root);

                if ($j + 1 < count($result)) {
                    $result[$j] ^= $result[$j + 1];
                }
            }

            $root = self::rsMultiply($root, 0x02);
        }

        return $result;
    }

    /**
     * @param  array<int, int>  $data
     * @param  array<int, int>  $divisor
     * @return array<int, int>
     */
    private static function rsRemainder(array $data, array $divisor): array
    {
        $result = array_fill(0, count($divisor), 0);

        foreach ($data as $byte) {
            $factor = $byte ^ array_shift($result);
            $result[] = 0;

            foreach ($divisor as $i => $coef) {
                $result[$i] ^= self::rsMultiply($coef, $factor);
            }
        }

        return $result;
    }

    private static function rsMultiply(int $x, int $y): int
    {
        $z = 0;

        for ($i = 7; $i >= 0; $i--) {
            $z = ($z << 1) ^ (($z >> 7) * 0x11D);
            $z ^= (($y >> $i) & 1) * $x;
        }

        return $z;
    }

    /** @param  array<int, int>  $bits */
    private static function appendBits(int $value, int $length, array &$bits): void
    {
        for ($i = $length - 1; $i >= 0; $i--) {
            $bits[] = ($value >> $i) & 1;
        }
    }

    private static function bit(int $x, int $i): bool
    {
        return (($x >> $i) & 1) !== 0;
    }
}
