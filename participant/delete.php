<?php
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../common/header.php";

$participant_id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($participant_id <= 0) {
    echo "<p>Invalid participant id.</p>";
    require_once __DIR__ . "/../common/footer.php";
    exit;
}

mysqli_begin_transaction($conn);

try {
    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM membership
         WHERE participant_id = ?"
    );
    if (!$stmt) {
        throw new Exception("Prepare failed (membership): " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "i", $participant_id);
    if (!mysqli_stmt_execute($stmt)) {
        $msg = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception("Delete failed (membership): " . $msg);
    }
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM participation
         WHERE participant_id = ?"
    );
    if (!$stmt) {
        throw new Exception("Prepare failed (participation): " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "i", $participant_id);
    if (!mysqli_stmt_execute($stmt)) {
        $msg = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception("Delete failed (participation): " . $msg);
    }
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM review
         WHERE participant_id = ?"
    );
    if (!$stmt) {
        throw new Exception("Prepare failed (review): " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "i", $participant_id);
    if (!mysqli_stmt_execute($stmt)) {
        $msg = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception("Delete failed (review): " . $msg);
    }
    mysqli_stmt_close($stmt);

    /* Finally delete participant */
    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM participant
         WHERE participant_id = ?"
    );
    if (!$stmt) {
        throw new Exception("Prepare failed (participant): " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "i", $participant_id);
    if (!mysqli_stmt_execute($stmt)) {
        $msg = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception("Delete failed (participant): " . $msg);
    }
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    if ($affected <= 0) {
        throw new Exception("Participant not found.");
    }

    mysqli_commit($conn);

    header("Location: list.php");
    exit;

} catch (Exception $ex) {
    mysqli_rollback($conn);
    echo "<p>" . htmlspecialchars($ex->getMessage()) . "</p>";
    echo '<p><a href="list.php">Back to list</a></p>';
}

require_once __DIR__ . "/../common/footer.php";
?>
