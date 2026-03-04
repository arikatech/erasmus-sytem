<?php
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../common/header.php";

$id = strtoupper(trim($_GET["id"] ?? ""));

$stmt = mysqli_prepare($conn, "DELETE FROM NGO WHERE ngo_id = ?");
mysqli_stmt_bind_param($stmt, "s", $id);
$ok = mysqli_stmt_execute($stmt);

if (!$ok) {
    echo "<p>Cannot delete this NGO because it is referenced by other tables.</p>";
    echo "<p>Error: " . htmlspecialchars(mysqli_stmt_error($stmt)) . "</p>";
    echo '<p><a href="list.php">Back</a></p>';
    mysqli_stmt_close($stmt);
    require_once __DIR__ . "/../common/footer.php";
    exit;
}

mysqli_stmt_close($stmt);
header("Location: list.php");
exit;
