<?php
include 'config.php';

$sql = "SELECT `id`, `classes_name`, `conf`, `x1`, `y1`, `x2`, `y2`, `size`, `timestamp` FROM `finger_classes`";
$result = $conn->query($sql);

if (!$result) {
    die("Query Error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Finger Detection Records</title>
    <style>
        table {
            border-collapse: collapse;
            width: 90%;
            margin: 20px auto;
        }
        th, td {
            border: 1px solid #999;
            padding: 8px 12px;
            text-align: center;
        }
        th {
            background-color: #eee;
        }
    </style>
</head>
<body>

<h2 style="text-align:center;">Finger Detection Records</h2>

<table>
    <tr>
        <th>#</th>
        <th>Class Name</th>
        <th>Size</th>
        <th>Confidence</th>
        <th>X1</th>
        <th>Y1</th>
        <th>X2</th>
        <th>Y2</th>
        <th>Timestamp</th>
    </tr>

    <?php
    if ($result->num_rows > 0):
        $no = 1;
        while ($rows = $result->fetch_assoc()):
    ?>
    <tr>
        <td><?= $no++ ?></td>
        <td><?= htmlspecialchars($rows['classes_name']) ?></td>
        <td><?= htmlspecialchars($rows['size']) ?></td>
        <td><?= htmlspecialchars($rows['conf']) ?></td>
        <td><?= htmlspecialchars($rows['x1']) ?></td>
        <td><?= htmlspecialchars($rows['y1']) ?></td>
        <td><?= htmlspecialchars($rows['x2']) ?></td>
        <td><?= htmlspecialchars($rows['y2']) ?></td>
        <td><?= htmlspecialchars($rows['timestamp']) ?></td>
    </tr>
    <?php
        endwhile;
    else:
    ?>
    <tr>
        <td colspan="9">No records found.</td>
    </tr>
    <?php endif; ?>
</table>

</body>
</html>
