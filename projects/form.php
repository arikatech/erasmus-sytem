<?php
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../common/header.php";

$errors = array();
$is_edit = false;

$project_id = null;
$title = "";
$start_date = "";
$end_date = "";
$country_code = "";
$ngo_id = "";

$eligible_countries = array();

$ngos = array();
$res = mysqli_query($conn, "SELECT ngo_id, name FROM ngo ORDER BY name");
if (!$res) {
    die(mysqli_error($conn));
}
while ($row = mysqli_fetch_assoc($res)) {
    $ngos[] = $row;
}

$countries = array();
$res = mysqli_query($conn, "SELECT country_code, country_name FROM country ORDER BY country_name");
if (!$res) {
    die(mysqli_error($conn));
}
while ($row = mysqli_fetch_assoc($res)) {
    $countries[] = $row;
}

if (isset($_GET["id"])) {
    $is_edit = true;
    $project_id = (int)$_GET["id"];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT project_id, title, start_date, end_date, country_code, ngo_id
         FROM project
         WHERE project_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $project_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if (!$row) {
        echo "<p>Project not found.</p>";
        require_once __DIR__ . "/../common/footer.php";
        exit;
    }

    $title = $row["title"];
    $start_date = $row["start_date"];
    $end_date = $row["end_date"];
    $country_code = $row["country_code"];
    $ngo_id = $row["ngo_id"];

    $res = mysqli_query(
        $conn,
        "SELECT country_code
         FROM project_eligible_country
         WHERE project_id = $project_id"
    );
    while ($r = mysqli_fetch_assoc($res)) {
        $eligible_countries[] = $r["country_code"];
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $is_edit = ($_POST["is_edit"] === "1");
    $project_id = (int)($_POST["project_id"] ?? 0);

    $title = trim($_POST["title"] ?? "");
    $start_date = $_POST["start_date"] ?? "";
    $end_date = $_POST["end_date"] ?? "";
    $country_code = $_POST["country_code"] ?? "";
    $ngo_id = (int)($_POST["ngo_id"] ?? 0);
    $eligible_countries = $_POST["eligible_countries"] ?? array();

    if ($title === "") $errors[] = "Title required";
    if ($start_date === "") $errors[] = "Start date required";
    if ($end_date === "") $errors[] = "End date required";
    if ($country_code === "") $errors[] = "Country required";
    if ($ngo_id <= 0) $errors[] = "NGO required";

    if (!$errors) {

        mysqli_begin_transaction($conn);

        try {
            if ($is_edit) {
                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE project
                     SET title = ?, start_date = ?, end_date = ?, country_code = ?, ngo_id = ?
                     WHERE project_id = ?"
                );
                mysqli_stmt_bind_param(
                    $stmt,
                    "ssssii",
                    $title,
                    $start_date,
                    $end_date,
                    $country_code,
                    $ngo_id,
                    $project_id
                );
            } else {
                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO project (title, start_date, end_date, country_code, ngo_id)
                     VALUES (?, ?, ?, ?, ?)"
                );
                mysqli_stmt_bind_param(
                    $stmt,
                    "ssssi",
                    $title,
                    $start_date,
                    $end_date,
                    $country_code,
                    $ngo_id
                );
            }

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);

            if (!$is_edit) {
                $project_id = mysqli_insert_id($conn);
            }

            mysqli_query(
                $conn,
                "DELETE FROM project_eligible_country
                 WHERE project_id = $project_id"
            );

            if ($eligible_countries) {
                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO project_eligible_country (project_id, country_code)
                     VALUES (?, ?)"
                );
                foreach ($eligible_countries as $cc) {
                    mysqli_stmt_bind_param($stmt, "is", $project_id, $cc);
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception(mysqli_stmt_error($stmt));
                    }
                }
                mysqli_stmt_close($stmt);
            }

            mysqli_commit($conn);
            header("Location: list.php");
            exit;

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = $e->getMessage();
        }
    }
}
?>

<h3><?php echo $is_edit ? "Edit Project" : "Add Project"; ?></h3>

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
<input type="hidden" name="project_id" value="<?php echo (int)$project_id; ?>">
<?php } ?>

<label>Title</label><br>
<input type="text" name="title" value="<?php echo htmlspecialchars($title); ?>"><br><br>

<label>NGO</label><br>
<select name="ngo_id">
<option value="">-- Select NGO --</option>
<?php foreach ($ngos as $n) { ?>
<option value="<?php echo $n["ngo_id"]; ?>"
<?php if ($n["ngo_id"] == $ngo_id) echo "selected"; ?>>
<?php echo htmlspecialchars($n["name"]); ?>
</option>
<?php } ?>
</select><br><br>

<label>Start Date</label><br>
<input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>"><br><br>

<label>End Date</label><br>
<input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>"><br><br>

<label>Main Country</label><br>
<select name="country_code">
<option value="">-- Select country --</option>
<?php foreach ($countries as $c) { ?>
<option value="<?php echo $c["country_code"]; ?>"
<?php if ($c["country_code"] === $country_code) echo "selected"; ?>>
<?php echo htmlspecialchars($c["country_name"]); ?>
</option>
<?php } ?>
</select><br><br>

<label>Eligible Countries</label><br>
<select name="eligible_countries[]" multiple size="6">
<?php foreach ($countries as $c) { ?>
<option value="<?php echo $c["country_code"]; ?>"
<?php if (in_array($c["country_code"], $eligible_countries)) echo "selected"; ?>>
<?php echo htmlspecialchars($c["country_name"]); ?>
</option>
<?php } ?>
</select><br><br>

<button type="submit">Save</button>
<a href="list.php">Cancel</a>
</form>

<?php
require_once __DIR__ . "/../common/footer.php";
?>
