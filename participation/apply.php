<?php
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../common/header.php";

$errors = array();

$participant_id = 0;
$project_id = 0;
$role = "";

$participants = array();
$projects = array();

$res = mysqli_query($conn, "SELECT participant_id, full_name, country_code FROM participant ORDER BY full_name");
if (!$res) {
    die("Failed to load participants: " . mysqli_error($conn));
}
while ($row = mysqli_fetch_assoc($res)) {
    $participants[] = $row;
}

$sql_projects = "
SELECT
  pr.project_id,
  pr.title,
  c.country_name AS main_country_name,
  n.name AS ngo_name
FROM project pr
LEFT JOIN country c ON c.country_code = pr.country_code
LEFT JOIN ngo n ON n.ngo_id = pr.ngo_id
ORDER BY pr.start_date DESC, pr.title ASC
";
$res = mysqli_query($conn, $sql_projects);
if (!$res) {
    die("Failed to load projects: " . mysqli_error($conn));
}
while ($row = mysqli_fetch_assoc($res)) {
    $projects[] = $row;
}

$allowed_roles = array("member", "leader");

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $participant_id = (int)($_POST["participant_id"] ?? 0);
    $project_id = (int)($_POST["project_id"] ?? 0);
    $role = trim($_POST["role"] ?? "");

    if ($participant_id <= 0) {
        $errors[] = "Please select a participant.";
    }
    if ($project_id <= 0) {
        $errors[] = "Please select a project.";
    }
    if (!in_array($role, $allowed_roles, true)) {
        $errors[] = "Please select a valid role.";
    }

    if (!$errors) {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT country_code
             FROM participant
             WHERE participant_id = ?"
        );
        if (!$stmt) {
            $errors[] = "Database error: " . mysqli_error($conn);
        } else {
            mysqli_stmt_bind_param($stmt, "i", $participant_id);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $p = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);

            if (!$p) {
                $errors[] = "Selected participant not found.";
            } else {
                $participant_country = $p["country_code"];

                $stmt = mysqli_prepare(
                    $conn,
                    "SELECT 1
                     FROM project_eligible_country
                     WHERE project_id = ? AND country_code = ?
                     LIMIT 1"
                );
                if (!$stmt) {
                    $errors[] = "Database error: " . mysqli_error($conn);
                } else {
                    mysqli_stmt_bind_param($stmt, "is", $project_id, $participant_country);
                    mysqli_stmt_execute($stmt);
                    $res = mysqli_stmt_get_result($stmt);
                    $eligible = ($res && mysqli_fetch_assoc($res)) ? true : false;
                    mysqli_stmt_close($stmt);

                    if (!$eligible) {
                        $errors[] = "This participant is not eligible for the selected project.";
                    }
                }

                $stmt = mysqli_prepare(
                    $conn,
                    "SELECT 1
                     FROM participation
                     WHERE participant_id = ? AND project_id = ?
                     LIMIT 1"
                );
                if (!$stmt) {
                    $errors[] = "Database error: " . mysqli_error($conn);
                } else {
                    mysqli_stmt_bind_param($stmt, "ii", $participant_id, $project_id);
                    mysqli_stmt_execute($stmt);
                    $res = mysqli_stmt_get_result($stmt);
                    $already = ($res && mysqli_fetch_assoc($res)) ? true : false;
                    mysqli_stmt_close($stmt);

                    if ($already) {
                        $errors[] = "This participant already applied to this project.";
                    }
                }

                if (!$errors) {
                    $stmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO participation (participant_id, project_id, status, applied_at, decision_at, role)
                         VALUES (?, ?, 'pending', NOW(), NULL, ?)"
                    );
                    if (!$stmt) {
                        $errors[] = "Database error: " . mysqli_error($conn);
                    } else {
                        mysqli_stmt_bind_param($stmt, "iis", $participant_id, $project_id, $role);
                        $ok = mysqli_stmt_execute($stmt);
                        if (!$ok) {
                            $errors[] = "Database error: " . mysqli_stmt_error($stmt);
                        }
                        mysqli_stmt_close($stmt);

                        if (!$errors) {
                            header("Location: list.php");
                            exit;
                        }
                    }
                }
            }
        }
    }
}
?>

<h3>Apply to Project</h3>

<?php if ($errors) { ?>
<ul>
  <?php foreach ($errors as $e) { ?>
    <li><?php echo htmlspecialchars($e); ?></li>
  <?php } ?>
</ul>
<?php } ?>

<form method="post">
  <label>Participant</label><br>
  <select name="participant_id">
    <option value="">-- Select participant --</option>
    <?php foreach ($participants as $p) { ?>
      <option value="<?php echo (int)$p["participant_id"]; ?>"
        <?php if ((int)$p["participant_id"] === (int)$participant_id) echo "selected"; ?>>
        <?php echo htmlspecialchars($p["full_name"] . " (" . $p["country_code"] . ")"); ?>
      </option>
    <?php } ?>
  </select>
  <br><br>

  <label>Project</label><br>
  <select name="project_id">
    <option value="">-- Select project --</option>
    <?php foreach ($projects as $pr) { ?>
      <option value="<?php echo (int)$pr["project_id"]; ?>"
        <?php if ((int)$pr["project_id"] === (int)$project_id) echo "selected"; ?>>
        <?php
          $ngo = $pr["ngo_name"] ?? "-";
          $mc = $pr["main_country_name"] ?? "-";
          echo htmlspecialchars($pr["title"] . " | " . $ngo . " | " . $mc);
        ?>
      </option>
    <?php } ?>
  </select>
  <br><br>

  <label>Role</label><br>
  <select name="role">
    <option value="">-- Select role --</option>
    <option value="member" <?php if ($role === "member") echo "selected"; ?>>Member</option>
    <option value="leader" <?php if ($role === "leader") echo "selected"; ?>>Leader</option>
  </select>
  <br><br>

  <button type="submit">Apply</button>
  <a href="list.php">Cancel</a>
</form>

<?php
require_once __DIR__ . "/../common/footer.php";
?>
