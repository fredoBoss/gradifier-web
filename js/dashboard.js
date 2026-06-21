const GRADE_COLORS = {
  '25BCP':  { border: 'border-blue-200',   text: 'text-blue-700',   sub: 'text-blue-400'   },
  '30BCP':  { border: 'border-orange-200', text: 'text-orange-700', sub: 'text-orange-400' },
  '33BCP':  { border: 'border-emerald-200',text: 'text-emerald-700',sub: 'text-emerald-400'},
  '30TR':   { border: 'border-purple-200', text: 'text-purple-700', sub: 'text-purple-400' },
  'IF36TR': { border: 'border-red-200',    text: 'text-red-700',    sub: 'text-red-400'    },
  'IF38TR': { border: 'border-yellow-200', text: 'text-yellow-700', sub: 'text-yellow-400' },
};

const PIE_COLORS = ['#10b981','#3b82f6','#f59e0b','#8b5cf6','#ec4899','#14b8a6'];

function setVal(id, val) {
  const el = document.getElementById(id);
  if (el) el.textContent = val;
}

async function loadDashboard() {
  const res = await fetch('/api/dashboard');
  if (res.status === 401) { window.location.href = '/login'; return; }
  const data = await res.json();

  const weights = data.weights;
  const labels  = data.labels;
  const total   = weights.reduce((a, b) => a + b, 0);
  const topIdx  = weights.indexOf(Math.max(...weights));

  const latestFormatted = data.latest_update
    ? new Date(data.latest_update).toLocaleString('en-PH', {
        month: 'short', day: 'numeric', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
      })
    : 'No data';

  setVal('val-total',   total.toFixed(2) + ' kg');
  setVal('val-top',     labels[topIdx] || '—');
  setVal('val-top-sub', weights[topIdx] ? weights[topIdx] + ' kg' : 'no data');
  setVal('val-batches', data.total_batches ?? '—');
  setVal('val-latest',  latestFormatted);

  // Render boxes per grade
  const container = document.getElementById('boxes-container');
  if (container) {
    container.innerHTML = labels.map(g => {
      const c = GRADE_COLORS[g] || { border: 'border-gray-200', text: 'text-gray-700', sub: 'text-gray-400' };
      return `<div class="bg-white rounded-2xl border ${c.border} shadow-sm p-4 flex flex-col gap-1">
        <span class="text-[11px] font-semibold uppercase tracking-wider ${c.text}">${g}</span>
        <div class="flex items-end gap-1.5 mt-1">
          <span class="text-2xl font-bold text-gray-800">${(data.boxes_per_grade[g] || 0).toLocaleString()}</span>
          <span class="text-xs ${c.sub} mb-0.5">boxes</span>
        </div>
      </div>`;
    }).join('');
  }

  // Render pie chart
  const ctx = document.getElementById('classPieChart');
  if (ctx && weights.some(w => w > 0)) {
    new Chart(ctx.getContext('2d'), {
      type: 'pie',
      data: {
        labels,
        datasets: [{
          data: weights,
          backgroundColor: PIE_COLORS,
          borderWidth: 2,
          borderColor: '#fff',
          hoverOffset: 12,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: 10 },
        plugins: {
          legend: {
            position: 'right',
            labels: {
              font: { size: 13, family: 'Poppins, sans-serif' },
              padding: 14, boxWidth: 12, boxHeight: 12,
              usePointStyle: true, pointStyle: 'circle',
              generateLabels: chart => {
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
            formatter: value => value > 0 ? `${value} kg` : '',
            color: '#fff',
            font: { size: 11, weight: '600' },
            anchor: 'center', align: 'center',
          },
          title: { display: false },
        },
      },
      plugins: [ChartDataLabels],
    });
  }
}

document.addEventListener('DOMContentLoaded', loadDashboard);
