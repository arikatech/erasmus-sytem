<?php
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../common/header.php";

$errors = array();
$is_edit = false;

$ngo_id = null;
$name = "";
$founded_date = "";
$email = "";
$country_code = "";

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

if (isset($_GET["id"])) {
    $is_edit = true;
    $ngo_id = (int) $_GET["id"];

    $stmt = mysqli_prepare($conn,
        "SELECT ngo_id, name, founded_date, email, country_code
         FROM NGO
         WHERE ngo_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $ngo_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$row) {
        echo "<p>NGO not found.</p>";
        require_once __DIR__ . "/../common/footer.php";
        exit;
    }

    $name = $row["name"];
    $founded_date = $row["founded_date"];
    $email = $row["email"];
    $country_code = $row["country_code"];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $founded_date = $_POST["founded_date"] ?? null;
    $email = trim($_POST["email"] ?? "");
    $country_code = $_POST["country_code"] ?? "";

    if ($name === "") {
        $errors[] = "Name is required.";
    }
    if ($email === "") {
        $errors[] = "Email is required.";
    }
    if ($country_code === "") {
        $errors[] = "Country is required.";
    }

    if (count($errors) === 0) {

        if (isset($_POST["is_edit"]) && $_POST["is_edit"] === "1") {
            $stmt = mysqli_prepare($conn,
                "UPDATE NGO
                 SET name = ?, founded_date = ?, email = ?, country_code = ?
                 WHERE ngo_id = ?"
            );
            mysqli_stmt_bind_param(
                $stmt,
                "ssssi",
                $name,
                $founded_date,
                $email,
                $country_code,
                $ngo_id
            );
        } else {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO NGO (name, founded_date, email, country_code)
                 VALUES (?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param(
                $stmt,
                "ssss",
                $name,
                $founded_date,
                $email,
                $country_code
            );
        }

        $ok = mysqli_stmt_execute($stmt);

        if (!$ok) {
            $errors[] = "Database error: " . mysqli_stmt_error($stmt);
        }

        mysqli_stmt_close($stmt);

        if (count($errors) === 0) {
            header("Location: list.php");
            exit;
        }
    }
}
?>

<h3><?php echo $is_edit ? "Edit NGO" : "Add NGO"; ?></h3>


<?php if ($errors) { ?>
  <ul>
    <?php foreach ($errors as $e) { ?>
      <li><?php echo htmlspecialchars($e); ?></li>
    <?php } ?>
  </ul>
<?php } ?>

<form method="post">
  <input type="hidden" name="is_edit" value="<?php echo $is_edit ? "1" : "0"; ?>">

  <label>Name</label><br>
  <input type="text" name="name"
         value="<?php echo htmlspecialchars($name); ?>"><br><br>

  <label>Founded Date</label><br>
  <input type="date" name="founded_date"
         value="<?php echo htmlspecialchars($founded_date); ?>"><br><br>

  <label>Email</label><br>
  <input type="email" name="email"
         value="<?php echo htmlspecialchars($email); ?>"><br><br>

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

  <button type="submit">Save</button>
  <a href="list.php">Cancel</a>
</form>



