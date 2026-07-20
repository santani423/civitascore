<?php

namespace Modules\Thesis\Enums;

enum ThesisStatus: string
{
    case Proposal = 'proposal';
    case Bimbingan = 'bimbingan';
    case SeminarProposal = 'seminar_proposal';
    case Penelitian = 'penelitian';
    case Sidang = 'sidang';
    case Selesai = 'selesai';
}
