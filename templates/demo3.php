<?php
include 'config.php';


$dateFilter = isset($_GET['date']) ? trim($_GET['date']) : null;
$farmFilter = isset($_GET['farm']) ? trim($_GET['farm']) : null;

if ($dateFilter && $farmFilter) {
    $stmt = $conn->prepare("SELECT * FROM `finger_classes` WHERE DATE(`timestamp`) = ? AND `Farm` = ?");
    $stmt->bind_param("ss", $dateFilter, $farmFilter);
    $stmt->execute();
    $result = $stmt->get_result();
} elseif ($dateFilter) {
    $stmt = $conn->prepare("SELECT * FROM `finger_classes` WHERE DATE(`timestamp`) = ?");
    $stmt->bind_param("s", $dateFilter);
    $stmt->execute();
    $result = $stmt->get_result();
} elseif ($farmFilter) {
    $stmt = $conn->prepare("SELECT * FROM `finger_classes` WHERE `Farm` = ?");
    $stmt->bind_param("s", $farmFilter);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql = "SELECT * FROM `finger_classes`";
    $result = $conn->query($sql);
}

if (!$result) {
    die("Query Error: " . $conn->error);
}


echo "<pre>";
print_r($_GET);
echo "</pre>";


?>




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <link rel="stylesheet" href="../src/styles.css" />
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <title>All Finger Class Data</title>


    <style>
        body {
            font-family: Arial, sans-serif;
        }

        h2 {
            text-align: center;
            margin-top: 20px;
        }

        table {
            border-collapse: collapse;
            width: 95%;
            margin: 20px auto;
        }

        th,
        td {
            border: 1px solid #999;
            padding: 8px 10px;
            text-align: center;
        }

        th {
            background-color: #eee;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>


</head>

<body>

    <h2>All Finger Class Data</h2>
    <div style="text-align: center; margin: 20px;">
        <button onclick="exportToPDF()" class="px-4 py-2 bg-green-500 text-white rounded">
            Export to PDF
        </button>
    </div>

    <div class="relative inline-block text-left">
        <label for="datePicker" class="block text-sm font-semibold text-gray-700 mb-1">Select Date</label>
        <input
            type="date"
            id="datePicker"
            value="<?= isset($_GET['date']) ? htmlspecialchars($_GET['date']) : '' ?>"
            class="bg-yellow-200 border border-yellow-300 text-gray-900 text-sm rounded-md focus:ring-yellow-400 focus:border-yellow-400 block w-48 p-2 cursor-pointer hover:shadow-md transition duration-200"
            onchange="filterByDate(this.value)" />
    </div>
    <!-- Block Dropdown as a <select> -->
    <div class="mt-6">
        <label for="farm" class="block text-sm font-medium text-gray-700 mb-1">Select Block</label>
        <select name="farm" id="farm" class="w-40 bg-yellow-200 border-yellow-300 text-gray-800 font-semibold px-3 py-2 rounded-md shadow-sm hover:bg-yellow-100 transition-colors duration-300">
            <option value="">-- Select Block --</option>
            <?php
            $farms = ['1' => 'Block 1', '2' => 'Block 2', '3' => 'Block 3', '4' => 'Block 4', '5' => 'Block 5', '6' => 'Block 6'];
            $selectedFarm = isset($_GET['farm']) ? $_GET['farm'] : '';
            foreach ($farms as $value => $label) {
                $selected = $selectedFarm == $value ? 'selected' : '';
                echo "<option value='$value' $selected>$label</option>";
            }
            ?>
        </select>
    </div>


    <!-- JavaScript -->
    <script>
        function toggleDropdown(dropdownId) {
            document.getElementById(dropdownId).classList.toggle('hidden');
        }

        function selectFarm(farmName) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('farm', farmName);
            window.location.search = urlParams.toString(); // Reloads with ?farm=Farm X
        }

        // Optional: Close dropdown on outside click
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('farmDropdown');
            const button = document.getElementById('farmButton');
            if (!button.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });
    </script>


    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Class Name</th>
                <th>Size</th>
                <th>Block</th>
                <th>Confidence</th>
                <th>X1</th>
                <th>Y1</th>
                <th>X2</th>
                <th>Y2</th>
                <th>Timestamp</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= $row['classes_name'] ?></td>
                    <td><?= $row['size'] ?></td>
                    <td><?= $row['Farm'] ?></td>
                    <td><?= $row['conf'] ?></td>
                    <td><?= $row['x1'] ?></td>
                    <td><?= $row['y1'] ?></td>
                    <td><?= $row['x2'] ?></td>
                    <td><?= $row['y2'] ?></td>
                    <td><?= $row['timestamp'] ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const rows = document.querySelectorAll("tbody tr");
            const rowsPerPage = 6;
            let currentPage = 1;
            const totalPages = Math.ceil(rows.length / rowsPerPage);

            const pageInfo = document.getElementById("pageNumbers");
            const prevBtn = document.getElementById("prevPage");
            const nextBtn = document.getElementById("nextPage");

            function displayRows(page) {
                const start = (page - 1) * rowsPerPage;
                const end = start + rowsPerPage;
                rows.forEach((row, index) => {
                    row.style.display = (index >= start && index < end) ? "" : "none";
                });
                pageInfo.textContent = `Page ${page} of ${totalPages}`;
                prevBtn.disabled = page === 1;
                nextBtn.disabled = page === totalPages;
            }

            prevBtn.addEventListener("click", () => {
                if (currentPage > 1) {
                    currentPage--;
                    displayRows(currentPage);
                }
            });

            nextBtn.addEventListener("click", () => {
                if (currentPage < totalPages) {
                    currentPage++;
                    displayRows(currentPage);
                }
            });

            displayRows(currentPage);
        });

        const allData = [
            <?php
            // Reset result pointer
            $result->data_seek(0);
            while ($row = $result->fetch_assoc()) {
                echo '["' . $row['id'] . '", "' . $row['classes_name'] . '", "' . $row['size'] . '", "' .
                    $row['conf'] . '", "' . $row['x1'] . '", "' . $row['y1'] . '", "' . $row['x2'] . '", "' .
                    $row['y2'] . '", "' . $row['timestamp'] . '"],';
            }
            ?>
        ];

        function exportToPDF() {
            const {
                jsPDF
            } = window.jspdf;
            const doc = new jsPDF();

            doc.text("All Finger Class Data", 14, 16);

            doc.autoTable({
                head: [
                    ["ID", "Class Name", "Size", "Confidence", "X1", "Y1", "X2", "Y2", "Timestamp"]
                ],
                body: allData,
                startY: 20,
                theme: 'grid',
                headStyles: {
                    fillColor: [22, 160, 133]
                },
            });

            doc.save("finger_classes_data.pdf");
        }
    </script>
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
<script>
    function filterByDate(selectedDate) {
        // Reload page with selected date as query parameter
        window.location.href = "?date=" + selectedDate;
    }

    function filterByFarm(farmName) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('farm', farmName);
        window.location.search = urlParams.toString();
    }
</script>



</html>

<?php $conn->close(); ?>