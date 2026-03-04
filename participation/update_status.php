<?php
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../common/header.php";

$errors = array();

$participant_id = isset($_GET["participant_id"]) ? (int)$_GET["participant_id"] : 0;
$project_id = isset($_GET["project_id"]) ? (int)$_GET["project_id"] : 0;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $participant_id = (int)($_POST["participant_id"] ?? 0);
    $project_id = (int)($_POST["project_id"] ?? 0);
}

if ($participant_id <= 0 || $project_id <= 0) {
    echo "<p>Invalid participation keys.</p>";
    require_once __DIR__ . "/../common/footer.php";
    exit;
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        pa.participant_id, pa.project_id, pa.status, pa.applied_at, pa.decision_at, pa.role,
        p.full_name AS participant_name,
        pr.title AS project_title
     FROM participation pa
     JOIN participant p ON p.participant_id = pa.participant_id
     JOIN project pr ON pr.project_id = pa.project_id
     WHERE pa.participant_id = ? AND pa.project_id = ?"
);
mysqli_stmt_bind_param($stmt, "ii", $participant_id, $project_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($stmt);

if (!$row) {
    echo "<p>Participation not found.</p>";
    require_once __DIR__ . "/../common/footer.php";
    exit;
}

$status = $row["status"];
$role = $row["role"] ?? "";

$allowed_status = array("pending", "accepted", "rejected");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $status = $_POST["status"] ?? "";
    $role = trim($_POST["role"] ?? "");

    if (!in_array($status, $allowed_status, true)) {
        $errors[] = "Invalid status.";
    }

    if (!$errors) {
        if ($status === "pending") {
            $stmt = mysqli_prepare(
                $conn,
                "UPDATE participation
                 SET status = ?, decision_at = NULL, role = NULL
                 WHERE participant_id = ? AND project_id = ?"
            );
            mysqli_stmt_bind_param($stmt, "sii", $status, $participant_id, $project_id);
        } else {
            $role_to_save = ($role === "") ? null : $role;

            $stmt = mysqli_prepare(
                $conn,
                "UPDATE participation
                 SET status = ?, decision_at = NOW(), role = ?
                 WHERE participant_id = ? AND project_id = ?"
            );
            mysqli_stmt_bind_param($stmt, "ssii", $status, $role_to_save, $participant_id, $project_id);
        }

        if (!mysqli_stmt_execute($stmt)) {
            $errors[] = "Database error: " . mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
        } else {
            mysqli_stmt_close($stmt);
            header("Location: list.php");
            exit;
        }
    }
}
?>

<h3>Update Participation Status</h3>

<?php if ($errors) { ?>
<ul>
  <?php foreach ($errors as $e) { ?>
    <li><?php echo htmlspecialchars($e); ?></li>
  <?php } ?>
</ul>
<?php } ?>

<p>
  <strong>Participant:</strong> <?php echo htmlspecialchars($row["participant_name"]); ?><br>
  <strong>Project:</strong> <?php echo htmlspecialchars($row["project_title"]); ?><br>
  <strong>Applied At:</strong> <?php echo htmlspecialchars($row["applied_at"]); ?>
</p>

<form method="post">
  <input type="hidden" name="participant_id" value="<?php echo (int)$participant_id; ?>">
  <input type="hidden" name="project_id" value="<?php echo (int)$project_id; ?>">

  <label>Status</label><br>
  <select name="status">
    <?php foreach ($allowed_status as $s) { ?>
      <option value="<?php echo htmlspecialchars($s); ?>"
        <?php if ($s === $status) echo "selected"; ?>>
        <?php echo htmlspecialchars($s); ?>
      </option>
    <?php } ?>
  </select>
  <br><br>

  <label>Role (optional)</label><br>
  <input type="text" name="role" value="<?php echo htmlspecialchars($role); ?>">
  <br><br>

  <button type="submit">Save</button>
  <a href="list.php">Cancel</a>
</form>

<?php
require_once __DIR__ . "/../common/footer.php";
?>
