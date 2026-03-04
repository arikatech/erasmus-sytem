<?php
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../common/header.php";

$project_id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($project_id <= 0) {
    echo "<p>Invalid project id.</p>";
    require_once __DIR__ . "/../common/footer.php";
    exit;
}

mysqli_begin_transaction($conn);

try {
    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM activity
         WHERE project_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $project_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM project_eligible_country
         WHERE project_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $project_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM participation
         WHERE project_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $project_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM review
         WHERE project_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $project_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM project
         WHERE project_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $project_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(mysqli_stmt_error($stmt));
    }

    if (mysqli_stmt_affected_rows($stmt) === 0) {
        throw new Exception("Project not found.");
    }

    mysqli_stmt_close($stmt);

    mysqli_commit($conn);

    header("Location: list.php");
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo '<p><a href="list.php">Back to list</a></p>';
}

require_once __DIR__ . "/../common/footer.php";
?>
