document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('dataTable').querySelector('tbody'); // More robust way to get tbody
    const prevPageButton = document.getElementById('prevPage');
    const nextPageButton = document.getElementById('nextPage');
    const pageNumbersSpan = document.getElementById('pageNumbers');

    let tableData = []; // Initialize as an empty array
    const rowsPerPage = 10;
    let currentPage = 1;

    // Fetches table data from the API and triggers initial render.
    function fetchData() {
        fetch('/api/reports') // Or your actual API endpoint
            .then(response => response.json())
            .then(data => {
                tableData = data;
                populateTable(currentPage); // Populate after fetching
                updatePaginationButtons();
            })
            .catch(error => {
                console.error('Error fetching data:', error);
                // Handle error, e.g., display a message to the user
                table.innerHTML = '<tr><td colspan="10">Error loading data.</td></tr>'; // Example error handling
                pageNumbersSpan.textContent = "Error";
                prevPageButton.disabled = true;
                nextPageButton.disabled = true;

            });
    }


    // Renders the current page's rows from fetched data into the table body.
    function populateTable(page) {
        if (!tableData || tableData.length === 0) {
            table.innerHTML = '<tr><td colspan="10">No data available.</td></tr>';
            return;
        }

        table.innerHTML = ''; // Clear existing rows

        const startIndex = (page - 1) * rowsPerPage;
        const endIndex = Math.min(startIndex + rowsPerPage, tableData.length);

        for (let i = startIndex; i < endIndex; i++) {
            const item = tableData[i];
            const row = table.insertRow();
            row.insertCell().textContent = i + 1;
            row.insertCell().textContent = item.farmNo;
            row.insertCell().textContent = item.date;
            row.insertCell().textContent = item.bcp25;
            row.insertCell().textContent = item.bcp30;
            row.insertCell().textContent = item.bcp33;
            row.insertCell().textContent = item.tr30;
            row.insertCell().textContent = item.if36tr;
            row.insertCell().textContent = item.if38tr;
            row.insertCell().textContent = item.total;
        }
    }

    // Disables prev/next buttons at boundaries and updates the page counter text.
    function updatePaginationButtons() {
        prevPageButton.disabled = currentPage === 1;
        nextPageButton.disabled = currentPage * rowsPerPage >= tableData.length;
        pageNumbersSpan.textContent = `Page ${currentPage} of ${Math.ceil(tableData.length / rowsPerPage) || 1}`; // Handle 0 data case
    }

    fetchData(); // Fetch the data initially

    prevPageButton.addEventListener('click', () => {
        currentPage = Math.max(1, currentPage - 1); // Prevent going below page 1
        populateTable(currentPage);
        updatePaginationButtons();
    });

    nextPageButton.addEventListener('click', () => {
        currentPage = Math.min(Math.ceil(tableData.length / rowsPerPage) || 1, currentPage + 1); // Prevent going beyond last page
        populateTable(currentPage);
        updatePaginationButtons();
    });
});