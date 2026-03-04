<?php
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../common/header.php";

$errors = array();
$is_edit = false;

$participant_id = null;
$full_name = "";
$email = "";
$country_code = "";

$ngo_id = "";
$join_date = "";
$role = "";

$countries = array();
$sql_countries = "SELECT country_code, country_name
                  FROM COUNTRY
                  ORDER BY country_name ASC";
$res_countries = mysqli_query($conn, $sql_countries);
if (!$res_countries) {
    die("Failed to load countries: " . mysqli_error($conn));
}
while ($row = mysqli_fetch_assoc($res_countries)) {
    $countries[] = $row;
}

$organizations = array();
$sql_organizations = "SELECT ngo_id, name
                      FROM NGO
                      ORDER BY name ASC";
$res_organizations = mysqli_query($conn, $sql_organizations);
if (!$res_organizations) {
    die("Failed to load organizations: " . mysqli_error($conn));
}
while ($row = mysqli_fetch_assoc($res_organizations)) {
    $organizations[] = $row;
}

if (isset($_GET["id"])) {
    $is_edit = true;
    $participant_id = (int) $_GET["id"];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT p.participant_id, p.full_name, p.email, p.country_code,
                m.ngo_id, m.join_date, m.role
         FROM PARTICIPANTS p
         LEFT JOIN MEMBERSHIP m ON m.participant_id = p.participant_id
         WHERE p.participant_id = ?"
    );
    if (!$stmt) {
        die("Prepare failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "i", $participant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if (!$row) {
        echo "<p>Participant not found.</p>";
        require_once __DIR__ . "/../common/footer.php";
        exit;
    }

    $full_name = $row["full_name"];
    $email = $row["email"];
    $country_code = $row["country_code"];

    $ngo_id = $row["ngo_id"] ?? "";
    $join_date = $row["join_date"] ?? "";
    $role = $row["role"] ?? "";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $is_edit = (isset($_POST["is_edit"]) && $_POST["is_edit"] === "1");

    if ($is_edit) {
        $participant_id = (int) ($_POST["participant_id"] ?? 0);
        if ($participant_id <= 0) {
            $errors[] = "Invalid participant id.";
        }
    }

    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $country_code = $_POST["country_code"] ?? "";

    $ngo_id = $_POST["ngo_id"] ?? "";
    $ngo_id = ($ngo_id === "" ? "" : (int)$ngo_id);

    $join_date = $_POST["join_date"] ?? "";
    $role = trim($_POST["role"] ?? "");

    if ($full_name === "") {
        $errors[] = "Full name is required.";
    }
    if ($email === "") {
        $errors[] = "Email is required.";
    }
    if ($country_code === "") {
        $errors[] = "Country is required.";
    }

    if ($ngo_id !== "") {
        if ($join_date === "") {
            $errors[] = "Join date is required if an NGO is selected.";
        }
        if ($role === "") {
            $errors[] = "Role is required if an NGO is selected.";
        }
    } else {
        $join_date = "";
        $role = "";
    }

    if (count($errors) === 0) {

        mysqli_begin_transaction($conn);

        try {
            if ($is_edit) {
                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE PARTICIPANT
                     SET full_name = ?, email = ?, country_code = ?
                     WHERE participant_id = ?"
                );
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . mysqli_error($conn));
                }
                mysqli_stmt_bind_param($stmt, "sssi", $full_name, $email, $country_code, $participant_id);
            } else {
                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO PARTICIPANT (full_name, email, country_code)
                     VALUES (?, ?, ?)"
                );
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . mysqli_error($conn));
                }
                mysqli_stmt_bind_param($stmt, "sss", $full_name, $email, $country_code);
            }

            $ok = mysqli_stmt_execute($stmt);
            if (!$ok) {
                $msg = mysqli_stmt_error($stmt);
                mysqli_stmt_close($stmt);
                throw new Exception("Database error (PARTICIPANTS): " . $msg);
            }
            mysqli_stmt_close($stmt);

            if (!$is_edit) {
                $participant_id = mysqli_insert_id($conn);
                if ($participant_id <= 0) {
                    throw new Exception("Failed to get new participant id.");
                }
            }

            if ($ngo_id === "") {
                $stmt = mysqli_prepare(
                    $conn,
                    "DELETE FROM MEMBERSHIP
                     WHERE participant_id = ?"
                );
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . mysqli_error($conn));
                }
                mysqli_stmt_bind_param($stmt, "i", $participant_id);
                $ok = mysqli_stmt_execute($stmt);
                if (!$ok) {
                    $msg = mysqli_stmt_error($stmt);
                    mysqli_stmt_close($stmt);
                    throw new Exception("Database error (MEMBERSHIP delete): " . $msg);
                }
                mysqli_stmt_close($stmt);
            } else {
                $stmt = mysqli_prepare(
                    $conn,
                    "SELECT 1
                     FROM MEMBERSHIP
                     WHERE participant_id = ?
                     LIMIT 1"
                );
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . mysqli_error($conn));
                }
                mysqli_stmt_bind_param($stmt, "i", $participant_id);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $exists = ($res && mysqli_fetch_assoc($res)) ? true : false;
                mysqli_stmt_close($stmt);

                if ($exists) {
                    $stmt = mysqli_prepare(
                        $conn,
                        "UPDATE MEMBERSHIP
                         SET ngo_id = ?, join_date = ?, role = ?
                         WHERE participant_id = ?"
                    );
                    if (!$stmt) {
                        throw new Exception("Prepare failed: " . mysqli_error($conn));
                    }
                    mysqli_stmt_bind_param($stmt, "issi", $ngo_id, $join_date, $role, $participant_id);
                } else {
                    $stmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO MEMBERSHIP (participant_id, ngo_id, join_date, role)
                         VALUES (?, ?, ?, ?)"
                    );
                    if (!$stmt) {
                        throw new Exception("Prepare failed: " . mysqli_error($conn));
                    }
                    mysqli_stmt_bind_param($stmt, "iiss", $participant_id, $ngo_id, $join_date, $role);
                }

                $ok = mysqli_stmt_execute($stmt);
                if (!$ok) {
                    $msg = mysqli_stmt_error($stmt);
                    mysqli_stmt_close($stmt);
                    throw new Exception("Database error (MEMBERSHIP): " . $msg);
                }
                mysqli_stmt_close($stmt);
            }

            mysqli_commit($conn);
            header("Location: list.php");
            exit;

        } catch (Exception $ex) {
            mysqli_rollback($conn);
            $errors[] = $ex->getMessage();
        }
    }
}
?>

<h3><?php echo $is_edit ? "Edit Participant" : "Add Participant"; ?></h3>

<?php if ($errors) { ?>
  <ul>
    <?php foreach ($errors as $e) { ?>
      <li><?php echo htmlspecialchars($e); ?></li>
    <?php } ?>
  </ul>
<?php } ?>

<form method="post">
  <input type="hidden" name="is_edit" value="<?php echo $is_edit ? "1" : "0"; ?>">
  <?php if ($is_edit) { ?>
    <input type="hidden" name="participant_id" value="<?php echo (int)$participant_id; ?>">
  <?php } ?>

  <label>Full Name</label><br>
  <input type="text" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>"><br><br>

  <label>Email</label><br>
  <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>"><br><br>

  <label>Country</label><br>
  <select name="country_code">
    <option value="">-- Select country --</option>
    <?php foreach ($countries as $c) { ?>
      <option value="<?php echo htmlspecialchars($c["country_code"]); ?>"
        <?php if ($c["country_code"] === $country_code) echo "selected"; ?>>
        <?php echo htmlspecialchars($c["country_name"]); ?>
      </option>
    <?php } ?>
  </select>
  <br><br>

  <label>NGO</label><br>
  <select name="ngo_id">
    <option value="">-- Not a member of an organization --</option>
    <?php foreach ($organizations as $o) { ?>
      <option value="<?php echo (int)$o["ngo_id"]; ?>"
        <?php if ((string)$o["ngo_id"] === (string)$ngo_id) echo "selected"; ?>>
        <?php echo htmlspecialchars($o["name"]); ?>
      </option>
    <?php } ?>
  </select>
  <br><br>

  <label>Join Date</label><br>
  <input type="date" name="join_date" value="<?php echo htmlspecialchars($join_date); ?>"><br><br>

  <label>Role</label><br>
  <input type="text" name="role" value="<?php echo htmlspecialchars($role); ?>"><br><br>

  <button type="submit">Save</button>
  <a href="list.php">Cancel</a>
</form>

<?php
require_once __DIR__ . "/../common/footer.php";
?>
