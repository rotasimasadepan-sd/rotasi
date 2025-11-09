<?php
require_once '../db.php';

// This file is called via navigator.sendBeacon() when a student leaves the exam tab.
// It resets their progress back to the first question and clears all their answers.
if (isset($_SESSION['id_partisipasi_ujian'])) {
    // Set current question index back to 0 (the first question)
    $_SESSION['soal_sekarang_idx'] = 0;
    
    // Clear any answers the student has already submitted
    $_SESSION['jawaban_siswa'] = [];
}

// No output is needed for this script.
// The browser will not process a response from a beacon request anyway.
exit;
?>
