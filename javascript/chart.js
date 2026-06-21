(() => {
  const ctx = document.getElementById("classPieChart").getContext("2d");

  const sse = new EventSource("http://localhost:4000/events");
  sse.onerror = () => sse.close();

  // Sets the text content of an element by id if it exists.
  const setVal = (id, val) => {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
  };

  fetch("DashBackend.php")
    .then((r) => r.json())
    .then((data) => {
      const weights = data.weights;
      const labels  = data.labels;

      const total   = weights.reduce((a, b) => a + b, 0);
      const topIdx  = weights.indexOf(Math.max(...weights));

      const latestFormatted = data.latest_update
        ? new Date(data.latest_update).toLocaleString("en-PH", {
            month: "short", day: "numeric", year: "numeric",
            hour: "2-digit", minute: "2-digit",
          })
        : "No data";

      setVal("val-total",   total.toFixed(2) + " kg");
      setVal("val-top",     labels[topIdx] || "—");
      setVal("val-top-sub", weights[topIdx] ? weights[topIdx] + " kg" : "no data");
      setVal("val-batches", data.total_batches ?? "—");
      setVal("val-latest",  latestFormatted);

      new Chart(ctx, {
        type: "pie",
        data: {
          labels,
          datasets: [{
            data: weights,
            backgroundColor: [
              "#10b981", // 25BCP  — emerald
              "#3b82f6", // 30BCP  — blue
              "#f59e0b", // 33BCP  — amber
              "#8b5cf6", // 30TR   — violet
              "#ec4899", // IF36TR — pink
              "#14b8a6", // IF38TR — teal
            ],
            borderWidth: 2,
            borderColor: "#fff",
            hoverOffset: 12,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          layout: { padding: 10 },
          plugins: {
            legend: {
              position: "right",
              labels: {
                font: { size: 13, family: "Poppins, sans-serif" },
                padding: 14,
                boxWidth: 12,
                boxHeight: 12,
                usePointStyle: true,
                pointStyle: "circle",
                generateLabels: (chart) => {
                  const ds = chart.data.datasets[0];
                  return chart.data.labels.map((label, i) => ({
                    text: `${label}  ${ds.data[i]} kg`,
                    fillStyle: ds.backgroundColor[i],
                    hidden: isNaN(ds.data[i]),
                    index: i,
                  }));
                },
              },
            },
            datalabels: {
              formatter: (value) => value > 0 ? `${value} kg` : "",
              color: "#fff",
              font: { size: 11, weight: "600" },
              anchor: "center",
              align: "center",
            },
            title: { display: false },
          },
        },
        plugins: [ChartDataLabels],
      });
    })
    .catch((err) => console.error("Chart load error:", err));
})();
