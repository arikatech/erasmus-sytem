<?php
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../common/header.php";

$sql = "
SELECT
  p.project_id,
  p.title,
  p.start_date,
  p.end_date,
  n.name AS ngo_name,
  cmain.country_name AS main_country_name,
  COALESCE(ec.eligible_list, '-') AS eligible_country_names
FROM project p
LEFT JOIN ngo n
  ON n.ngo_id = p.ngo_id
LEFT JOIN country cmain
  ON cmain.country_code = p.country_code
LEFT JOIN (
  SELECT
    pec.project_id,
    GROUP_CONCAT(DISTINCT c.country_name ORDER BY c.country_name SEPARATOR ', ') AS eligible_list
  FROM project_eligible_country pec
  JOIN country c
    ON c.country_code = pec.country_code
  GROUP BY pec.project_id
) ec
  ON ec.project_id = p.project_id
ORDER BY p.start_date DESC, p.title ASC
";

$res = mysqli_query($conn, $sql);

if (!$res) {
    die("Query failed: " . mysqli_error($conn));
}
?>

<h3>Projects</h3>
<p><a href="form.php">Add Project</a></p>

<table border="1" cellpadding="6" cellspacing="0">
  <tr>
    <th>Title</th>
    <th>Organizer (NGO)</th>
    <th>Start</th>
    <th>End</th>
    <th>Country</th>
    <th>Eligible Countries</th>
    <th>Actions</th>
  </tr>

  <?php while ($row = mysqli_fetch_assoc($res)) { ?>
    <tr>
      <td><?php echo htmlspecialchars($row["title"]); ?></td>
      <td>
        <?php
          $org = $row["ngo_name"];
          echo ($org === null || $org === "") ? "-" : htmlspecialchars($org);
        ?>
      </td>
      <td><?php echo htmlspecialchars($row["start_date"]); ?></td>
      <td><?php echo htmlspecialchars($row["end_date"]); ?></td>
      <td>
        <?php
          $mc = $row["main_country_name"];
          echo ($mc === null || $mc === "") ? "-" : htmlspecialchars($mc);
        ?>
      </td>
      <td><?php echo htmlspecialchars($row["eligible_country_names"]); ?></td>
      <td>
        <a href="form.php?id=<?php echo urlencode($row["project_id"]); ?>">Edit</a>
        |
        <a href="delete.php?id=<?php echo urlencode($row["project_id"]); ?>"
           onclick="return confirm('Delete this project?');">
           Delete
        </a>
      </td>
    </tr>
  <?php } ?>

</table>

<?php
require_once __DIR__ . "/../common/footer.php";
?>
