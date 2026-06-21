// Toggles visibility of the specified dropdown element.
function toggleDropdown(dropdownId) {
    const dropdown = document.getElementById(dropdownId);
    dropdown.classList.toggle("hidden");
  }

  // Closes all open dropdowns when clicking outside a cursor-pointer element.
  window.onclick = function (event) {
    if (!event.target.matches(".cursor-pointer")) {
      const dropdowns = document.querySelectorAll(".absolute");
      dropdowns.forEach((dropdown) => {
        if (!dropdown.classList.contains("hidden")) {
          dropdown.classList.add("hidden");
        }
      });
    }
  };

  // Data for the table
  const tableData = [
    {
      farmNo: "Block 1",
      date: "2024-07-26",
      bc25: 10,
      bc30: 15,
      bc33: 20,
      tr30: 5,
      if36tr: 8,
      if38tr: 12,
    },
    {
      farmNo: "Block 2",
      date: "2024-07-25",
      bc25: 12,
      bc30: 18,
      bc33: 22,
      tr30: 6,
      if36tr: 9,
      if38tr: 13,
    },
    {
      farmNo: "Block 3",
      date: "2024-07-24",
      bc25: 8,
      bc30: 13,
      bc33: 18,
      tr30: 4,
      if36tr: 7,
      if38tr: 11,
    },
    {
      farmNo: "Block 4",
      date: "2024-07-23",
      bc25: 15,
      bc30: 20,
      bc33: 25,
      tr30: 7,
      if36tr: 10,
      if38tr: 14,
    },
    {
      farmNo: "Block 5",
      date: "2024-07-22",
      bc25: 11,
      bc30: 16,
      bc33: 21,
      tr30: 5,
      if36tr: 8,
      if38tr: 12,
    },

    {
      farmNo: "Block 1",
      date: "2024-07-26",
      bc25: 10,
      bc30: 15,
      bc33: 20,
      tr30: 5,
      if36tr: 8,
      if38tr: 12,
    },
    {
      farmNo: "Block 2",
      date: "2024-07-25",
      bc25: 12,
      bc30: 18,
      bc33: 22,
      tr30: 6,
      if36tr: 9,
      if38tr: 13,
    },
    {
      farmNo: "Block 3",
      date: "2024-07-24",
      bc25: 8,
      bc30: 13,
      bc33: 18,
      tr30: 4,
      if36tr: 7,
      if38tr: 11,
    },
    {
      farmNo: "Block 4",
      date: "2024-07-23",
      bc25: 15,
      bc30: 20,
      bc33: 25,
      tr30: 7,
      if36tr: 10,
      if38tr: 14,
    },
    {
      farmNo: "Block 5",
      date: "2024-07-22",
      bc25: 11,
      bc30: 16,
      bc33: 21,
      tr30: 5,
      if36tr: 8,
      if38tr: 12,
    },
  ];

  const rowsPerPage = 10;
  let currentPage = 1;

  // Renders a slice of tableData into the table body for the given page number.
  function populateTable(page) {
    const tableBody = document.querySelector("#dataTable tbody");
    tableBody.innerHTML = ""; // Clear existing rows

    const startIndex = (page - 1) * rowsPerPage;
    const endIndex = startIndex + rowsPerPage;
    const pageData = tableData.slice(startIndex, endIndex);

    pageData.forEach((item, index) => {
      const row = document.createElement("tr");
      row.innerHTML = `
        <td class="px-6 py-4 whitespace-nowrap">${
          startIndex + index + 1
        }</td>
        <td class="px-6 py-4 whitespace-nowrap">${item.farmNo}</td>
        <td class="px-6 py-4 whitespace-nowrap">${item.date}</td>
        <td class="px-6 py-4 whitespace-nowrap">${item.bc25}</td>
        <td class="px-6 py-4 whitespace-nowrap">${item.bc30}</td>
        <td class="px-6 py-4 whitespace-nowrap">${item.bc33}</td>
        <td class="px-6 py-4 whitespace-nowrap">${item.tr30}</td>
        <td class="px-6 py-4 whitespace-nowrap">${item.if36tr}</td>
        <td class="px-6 py-4 whitespace-nowrap">${item.if38tr}</td>
        <td class="px-6 py-4 whitespace-nowrap">${
          item.bc25 +
          item.bc30 +
          item.bc33 +
          item.tr30 +
          item.if36tr +
          item.if38tr
        }</td>
      `;
      tableBody.appendChild(row);
    });
  }

  // Updates page label and enables/disables prev/next buttons based on current page.
  function updatePagination() {
    const totalPages = Math.ceil(tableData.length / rowsPerPage);
    document.getElementById(
      "pageNumbers"
    ).textContent = `Page ${currentPage} of ${totalPages}`;

    document.getElementById("prevPage").disabled = currentPage === 1;
    document.getElementById("nextPage").disabled =
      currentPage === totalPages;
  }

  document.getElementById("prevPage").addEventListener("click", () => {
    if (currentPage > 1) {
      currentPage--;
      populateTable(currentPage);
      updatePagination();
    }
  });

  document.getElementById("nextPage").addEventListener("click", () => {
    const totalPages = Math.ceil(tableData.length / rowsPerPage);
    if (currentPage < totalPages) {
      currentPage++;
      populateTable(currentPage);
      updatePagination();
    }
  });

  populateTable(currentPage);
  updatePagination();