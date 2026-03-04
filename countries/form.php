<?php
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../common/header.php";

$errors = array();
$is_edit = false;

$country_code = "";
$country_name = "";

if (isset($_GET["code"])) {
    $is_edit = true;
    $country_code = strtoupper(trim($_GET["code"]));

    $stmt = mysqli_prepare($conn,
        "SELECT country_code, country_name
         FROM COUNTRY
         WHERE country_code = ?"
    );
    mysqli_stmt_bind_param($stmt, "s", $country_code);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if (!$row) {
        echo "<p>Country not found.</p>";
        require_once __DIR__ . "/../common/footer.php";
        exit;
    }

    $country_name = $row["country_name"];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $country_code = strtoupper(trim($_POST["country_code"] ?? ""));
    $country_name = trim($_POST["country_name"] ?? "");

    if ($country_code === "" || strlen($country_code) != 2) {
        $errors[] = "Country code must be exactly 2 letters.";
    }
    if ($country_name === "") {
        $errors[] = "Country name is required.";
    }

    if (count($errors) == 0) {
        if (isset($_POST["is_edit"]) && $_POST["is_edit"] === "1") {
            $stmt = mysqli_prepare($conn,
                "UPDATE COUNTRY
                 SET country_name = ?
                 WHERE country_code = ?"
            );
            mysqli_stmt_bind_param($stmt, "ss", $country_name, $country_code);
        } else {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO COUNTRY (country_code, country_name)
                 VALUES (?, ?)"
            );
            mysqli_stmt_bind_param($stmt, "ss", $country_code, $country_name);
        }

        $ok = mysqli_stmt_execute($stmt);

        if (!$ok) {
            $errors[] = "Database error: " . mysqli_stmt_error($stmt);
        }

        mysqli_stmt_close($stmt);

        if (count($errors) == 0) {
            header("Location: list.php");
            exit;
        }
    }
}
?>

<h3><?php echo $is_edit ? "Edit Country" : "Add Country"; ?></h3>

<?php if (count($errors) > 0) { ?>
  <ul>
    <?php foreach ($errors as $e) { ?>
      <li><?php echo htmlspecialchars($e); ?></li>
    <?php } ?>
  </ul>
<?php } ?>

<form method="post" action="form.php<?php echo $is_edit ? '?code=' . urlencode($country_code) : ''; ?>">
  <input type="hidden" name="is_edit" value="<?php echo $is_edit ? "1" : "0"; ?>">

  <label>Country Code (2 letters)</label><br>
  <input type="text" name="country_code" maxlength="2"
         value="<?php echo htmlspecialchars($country_code); ?>"
         <?php echo $is_edit ? "readonly" : ""; ?>>
  <br><br>

  <label>Country Name</label><br>
  <input type="text" name="country_name" maxlength="80"
         value="<?php echo htmlspecialchars($country_name); ?>">
  <br><br>

  <button type="submit">Save</button>
  <a href="list.php">Cancel</a>
</form>

<?php
require_once __DIR__ . "/../common/footer.php";
?>
