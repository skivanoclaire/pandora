<?php

namespace App\Http\Controllers;

use App\Models\Integrity\LedgerAnchor;
use App\Models\Integrity\LogPresensiPandora;
use App\Services\HashChainService;
use App\Services\MerkleTreeService;

class IntegrityController extends Controller
{
    /**
     * GET /integritas/anchor — daftar anchor dengan status.
     */
    public function index()
    {
        $anchors = LedgerAnchor::query()
            ->orderByDesc('tanggal')
            ->paginate(30);

        return view('integritas.index', compact('anchors'));
    }

    /**
     * GET /integritas/download/{date}.ots — download file OTS proof.
     */
    public function downloadProof(string $date)
    {
        $anchor = LedgerAnchor::where('tanggal', $date)->firstOrFail();

        $proof = $anchor->ots_proof_complete ?? $anchor->ots_proof_incomplete;

        if (!$proof) {
            abort(404, 'OTS proof belum tersedia untuk tanggal ini.');
        }

        $binary = base64_decode($proof);
        $filename = "{$date}.ots";

        return response($binary)
            ->header('Content-Type', 'application/octet-stream')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * GET /integritas/verify/{date} — verifikasi integritas tanggal tertentu.
     */
    public function verifyDate(string $date, HashChainService $hashChain, MerkleTreeService $merkle)
    {
        // Ambil record untuk tanggal ini
        $records = LogPresensiPandora::whereDate('waktu', $date)
            ->orderBy('sequence_no')
            ->get();

        $recordCount = $records->count();

        if ($recordCount === 0) {
            return response()->json([
                'record_count' => 0,
                'merkle_root_hex' => null,
                'chain_valid' => null,
                'btc_status' => null,
                'ots_available' => false,
            ]);
        }

        // Hitung Merkle root
        $hashes = $records->pluck('hash_current')->toArray();
        $merkleRoot = $merkle->root($hashes);

        // Verifikasi chain untuk range sequence_no tanggal ini
        $seqFrom = $records->first()->sequence_no;
        $seqTo = $records->last()->sequence_no;
        $chainResult = $hashChain->verifyChain($seqFrom, $seqTo);

        // Cek anchor status
        $anchor = LedgerAnchor::where('tanggal', $date)->first();
        $btcStatus = $anchor?->status;
        $otsAvailable = $anchor && ($anchor->ots_proof_complete || $anchor->ots_proof_incomplete);

        return response()->json([
            'record_count' => $recordCount,
            'merkle_root_hex' => $merkleRoot,
            'chain_valid' => $chainResult['verified'],
            'btc_status' => $btcStatus,
            'ots_available' => $otsAvailable,
        ]);
    }
}
