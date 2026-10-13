<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\FileManagement\Models\FileUpload;

class ChatMessage extends PmbModel
{
    protected $table = 'pmb_chat_messages';

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ChatThread, $this>
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(ChatThread::class, 'thread_id');
    }

    /**
     * @return BelongsTo<FileUpload, $this>
     */
    public function attachment(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'attachment_file_id');
    }
}
