<?php
include 'config.php';

$sql = "
SELECT 
    Farm,
    DATE(timestamp) as date,
    SUM(CASE WHEN Classes = '25BCP' THEN 1 ELSE 0 END) AS `25BCP`,
    SUM(CASE WHEN Classes = '30BCP' THEN 1 ELSE 0 END) AS `30BCP`,
    SUM(CASE WHEN Classes = '33BCP' THEN 1 ELSE 0 END) AS `33BCP`,
    SUM(CASE WHEN Classes = '30TR' THEN 1 ELSE 0 END) AS `30TR`,
    SUM(CASE WHEN Classes = 'IF36TR' THEN 1 ELSE 0 END) AS `IF36TR`,
    SUM(CASE WHEN Classes = 'IF38TR' THEN 1 ELSE 0 END) AS `IF38TR`,
    COUNT(*) AS total
FROM Finger_classes
GROUP BY Farm, DATE(timestamp)
ORDER BY DATE(timestamp) DESC
";
$result = $conn->query($sql);
$no = 1;
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


    <table class="min-w-full divide-y divide-gray-200">
        <thead>
            <tr>
                <th class="px-6 py-3 text-left text-md font-medium text-black uppercase tracking-wider">No.</th>
                <th class="px-6 py-3 text-left text-md font-medium text-black uppercase tracking-wider">Block No.</th>
                <th class="px-6 py-3 text-left text-md font-medium text-black uppercase tracking-wider">Date</th>
                <th class="px-6 py-3 text-left text-md font-medium text-black uppercase tracking-wider">25BCP</th>
                <th class="px-6 py-3 text-left text-md font-medium text-black uppercase tracking-wider">30BCP</th>
                <th class="px-6 py-3 text-left text-md font-medium text-black uppercase tracking-wider">33BCP</th>
                <th class="px-6 py-3 text-left text-md font-medium text-black uppercase tracking-wider">30TR</th>
                <th class="px-6 py-3 text-left text-md font-medium text-black uppercase tracking-wider">IF36TR</th>
                <th class="px-6 py-3 text-left text-md font-medium text-black uppercase tracking-wider">IF38TR</th>
                <th class="px-6 py-3 text-left text-md font-medium text-black uppercase tracking-wider">Total</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td class="px-6 py-4"><?php echo $no++; ?></td>
                    <td class="px-6 py-4"><?php echo $row['Farm']; ?></td>
                    <td class="px-6 py-4"><?php echo $row['date']; ?></td>
                    <td class="px-6 py-4"><?php echo $row['25BCP']; ?></td>
                    <td class="px-6 py-4"><?php echo $row['30BCP']; ?></td>
                    <td class="px-6 py-4"><?php echo $row['33BCP']; ?></td>
                    <td class="px-6 py-4"><?php echo $row['30TR']; ?></td>
                    <td class="px-6 py-4"><?php echo $row['IF36TR']; ?></td>
                    <td class="px-6 py-4"><?php echo $row['IF38TR']; ?></td>
                    <td class="px-6 py-4"><?php echo $row['total']; ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <?php $conn->close(); ?>
    <div class="mt-4 flex justify-center">
        <button
            id="prevPage"
            class="px-4 py-2 mx-2 bg-gray-200 rounded disabled">
            &lt; Prev
        </button>
        <span id="pageNumbers" class="mx-2">Page 1 of 1</span>
        <button id="nextPage" class="px-4 py-2 mx-2 bg-gray-200 rounded">
            Next &gt;
        </button>
    </div>

</body>

</html>