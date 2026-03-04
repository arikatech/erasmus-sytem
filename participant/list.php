<?php
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../common/header.php";

/*
  In your schema:
  - Participant info is in PARTICIPANT
  - Organization membership is in MEMBERSHIP (participant_id -> ngo_id)
  - NGO name is in NGO
*/
$sql = "SELECT p.participant_id,
               p.full_name,
               p.email,
               p.country_code,
               n.name AS ngo_name
        FROM PARTICIPANT p
        LEFT JOIN MEMBERSHIP m ON m.participant_id = p.participant_id
        LEFT JOIN NGO n ON n.ngo_id = m.ngo_id
        ORDER BY p.full_name ASC";

$res = mysqli_query($conn, $sql);

if (!$res) {
    die("Query failed: " . mysqli_error($conn));
}
?>

<h3>PARTICIPANT</h3>
<p><a href="form.php">Add Participant</a></p>

<table border="1" cellpadding="6" cellspacing="0">
  <tr>
    <th>Name</th>
    <th>Email</th>
    <th>Location</th>
    <th>Organization</th>
    <th>Actions</th>
  </tr>

  <?php while ($row = mysqli_fetch_assoc($res)) { ?>
    <tr>
      <td><?php echo htmlspecialchars($row["full_name"]); ?></td>
      <td><?php echo htmlspecialchars($row["email"]); ?></td>
      <td><?php echo htmlspecialchars($row["country_code"]); ?></td>
      <td>
        <?php
          $org = $row["ngo_name"];
          echo ($org === null || $org === "") ? "-" : htmlspecialchars($org);
        ?>
      </td>
      <td>
        <a href="form.php?id=<?php echo urlencode($row["participant_id"]); ?>">Edit</a>
        |
        <a href="delete.php?id=<?php echo urlencode($row["participant_id"]); ?>"
           onclick="return confirm('Delete this participant?');">
           Delete
        </a>
      </td>
    </tr>
  <?php } ?>

</table>

<?php
require_once __DIR__ . "/../common/footer.php";
?>
