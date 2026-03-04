<?php
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../common/header.php";

$sql = "
SELECT
  pa.participant_id,
  pa.project_id,
  pa.status,
  pa.applied_at,
  pa.decision_at,
  pa.role,
  p.full_name AS participant_name,
  pr.title AS project_title
FROM participation pa
JOIN participant p
  ON p.participant_id = pa.participant_id
JOIN project pr
  ON pr.project_id = pa.project_id
ORDER BY pa.applied_at DESC, p.full_name ASC, pr.title ASC
";

$res = mysqli_query($conn, $sql);

if (!$res) {
    die("Query failed: " . mysqli_error($conn));
}
?>

<h3>Participations</h3>
<p>
  <a href="apply.php">Apply to projects</a>
</p>

<table border="1" cellpadding="6" cellspacing="0">
  <tr>
    <th>Participant</th>
    <th>Project</th>
    <th>Status</th>
    <th>Applied At</th>
    <th>Decision At</th>
    <th>Role</th>
    <th>Actions</th>
  </tr>

  <?php while ($row = mysqli_fetch_assoc($res)) { ?>
    <tr>
      <td><?php echo htmlspecialchars($row["participant_name"]); ?></td>
      <td><?php echo htmlspecialchars($row["project_title"]); ?></td>
      <td><?php echo htmlspecialchars($row["status"]); ?></td>
      <td><?php echo htmlspecialchars($row["applied_at"]); ?></td>
      <td>
        <?php
          $d = $row["decision_at"];
          echo ($d === null || $d === "") ? "-" : htmlspecialchars($d);
        ?>
      </td>
      <td>
        <?php
          $r = $row["role"];
          echo ($r === null || $r === "") ? "-" : htmlspecialchars($r);
        ?>
      </td>
      <td>
        <a href="update_status.php?participant_id=<?php echo urlencode($row["participant_id"]); ?>&project_id=<?php echo urlencode($row["project_id"]); ?>">
          Update Status
        </a>
      </td>
    </tr>
  <?php } ?>

</table>

<?php
require_once __DIR__ . "/../common/footer.php";
?>
