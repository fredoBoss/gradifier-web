<?php
include 'config.php';

$sql = "SELECT 
            COUNT(classes_name) AS total_classes,
            SUM(conf) AS total_conf,
            SUM(x1) AS total_x1,
            SUM(y1) AS total_y1,
            SUM(x2) AS total_x2,
            SUM(y2) AS total_y2
        FROM finger_classes";

$result = $conn->query($sql);

if (!$result) {
    die("Query Error: " . $conn->error);
}

$row = $result->fetch_assoc(); // single result row
?>
<!DOCTYPE html>
<html>

<head>
    <title>Total Summary</title>
    <style>
        table {
            border-collapse: collapse;
            width: 90%;
            margin: 20px auto;
        }

        th,
        td {
            border: 1px solid #999;
            padding: 8px 12px;
            text-align: center;
        }

        th {
            background-color: #eee;
        }

        td {
            font-weight: bold;
        }
    </style>
</head>

<body>

    <h2 style="text-align:center;">Total Detection Summary</h2>
    <div class="mt-6 relative select-none">
      
        <table>
            <thead>
                <tr>
                    <th>Class Name (Count)</th>
                    <th>Confidence (Sum)</th>
                    <th>X1 (Sum)</th>
                    <th>Y1 (Sum)</th>
                    <th>X2 (Sum)</th>
                    <th>Y2 (Sum)</th>
                </tr>
            </thead>
            <tbody>

                <tr>
                    <td><?= $row['total_classes'] ?></td>
                    <td><?= number_format($row['total_conf'], 2) ?></td>
                    <td><?= $row['total_x1'] ?></td>
                    <td><?= $row['total_y1'] ?></td>
                    <td><?= $row['total_x2'] ?></td>
                    <td><?= $row['total_y2'] ?></td>
                </tr>
            </tbody>
        </table>

</body>

</html>