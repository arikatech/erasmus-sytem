<?php
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../common/header.php";

$sql = "SELECT ngo_id, name, founded_date, email, country_code
        FROM NGO
        ORDER BY name ASC";

$res = mysqli_query($conn, $sql);

if (!$res) {
    die("Query failed: " . mysqli_error($conn));
}
?>

<h3>NGOs</h3>
<p><a href="form.php">Add NGO</a></p>

<table border="1" cellpadding="6" cellspacing="0">
  <tr>
    <th>Name</th>
    <th>Founded in</th>
    <th>Email</th>
    <th>Location</th>
    <th>Actions</th>
  </tr>

  <?php while ($row = mysqli_fetch_assoc($res)) { ?>
    <tr>
      <td><?php echo htmlspecialchars($row["name"]); ?></td>
      <td><?php echo htmlspecialchars($row["founded_date"]); ?></td>
      <td><?php echo htmlspecialchars($row["email"]); ?></td>
      <td><?php echo htmlspecialchars($row["country_code"]); ?></td>
      <td>
        <a href="form.php?id=<?php echo urlencode($row["ngo_id"]); ?>">Edit</a>
        |
        <a href="delete.php?id=<?php echo urlencode($row["ngo_id"]); ?>"
           onclick="return confirm('Delete this NGO?');">
           Delete
        </a>
      </td>
    </tr>
  <?php } ?>

</table>

<?php
require_once __DIR__ . "/../common/footer.php";
?>
