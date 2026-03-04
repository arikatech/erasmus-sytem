<?php
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../common/header.php";

$sql = "SELECT country_code, country_name
        FROM COUNTRY
        ORDER BY country_name ASC";

$res = mysqli_query($conn, $sql);

if (!$res) {
    die("Query failed: " . mysqli_error($conn));
}
?>

<h3>Countries</h3>
<p><a href="form.php">Add Country</a></p>

<table border="1" cellpadding="6" cellspacing="0">
  <tr>
    <th>Code</th>
    <th>Name</th>
    <th>Actions</th>
  </tr>

  <?php while ($row = mysqli_fetch_assoc($res)) { ?>
    <tr>
      <td><?php echo htmlspecialchars($row["country_code"]); ?></td>
      <td><?php echo htmlspecialchars($row["country_name"]); ?></td>
      <td>
        <a href="form.php?code=<?php echo urlencode($row["country_code"]); ?>">Edit</a>
        |
        <a href="delete.php?code=<?php echo urlencode($row["country_code"]); ?>"
           onclick="return confirm('Delete this country?');">
           Delete
        </a>
      </td>
    </tr>
  <?php } ?>

</table>

<?php
require_once __DIR__ . "/../common/footer.php";
?>
