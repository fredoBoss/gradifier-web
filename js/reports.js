const GRADES = ['25BCP','30BCP','33BCP','30TR','IF36TR','IF38TR'];
const PALETTE = {
  '25BCP':  { border:'#3b82f6', bg:'rgba(59,130,246,0.08)'  },
  '30BCP':  { border:'#f97316', bg:'rgba(249,115,22,0.08)'  },
  '33BCP':  { border:'#10b981', bg:'rgba(16,185,129,0.08)'  },
  '30TR':   { border:'#8b5cf6', bg:'rgba(139,92,246,0.08)'  },
  'IF36TR': { border:'#ef4444', bg:'rgba(239,68,68,0.08)'   },
  'IF38TR': { border:'#eab308', bg:'rgba(234,179,8,0.08)'   },
};
const BOX_COLORS = {
  '25BCP': {border:'border-blue-200',  text:'text-blue-700',  icon:'text-blue-400'},
  '30BCP': {border:'border-orange-200',text:'text-orange-700',icon:'text-orange-400'},
  '33BCP': {border:'border-emerald-200',text:'text-emerald-700',icon:'text-emerald-400'},
  '30TR':  {border:'border-purple-200',text:'text-purple-700',icon:'text-purple-400'},
  'IF36TR':{border:'border-red-200',   text:'text-red-700',   icon:'text-red-400'},
  'IF38TR':{border:'border-yellow-200',text:'text-yellow-700',icon:'text-yellow-400'},
};

let chartInstance = null;
let availableDates = [];
let activeChartType = 'line';

function fmtDate(d) {
  return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');
}

function renderBoxes(boxesPerGrade) {
  const el = document.getElementById('boxes-container');
  if (!el) return;
  el.innerHTML = GRADES.map(g => {
    const c = BOX_COLORS[g];
    return `<div class="bg-white rounded-2xl border ${c.border} shadow-sm p-4 flex flex-col gap-1">
      <span class="text-[11px] font-semibold uppercase tracking-wider ${c.text}">${g}</span>
      <div class="flex items-end gap-1.5 mt-1">
        <span class="text-2xl font-bold text-gray-800">${(boxesPerGrade[g]||0).toLocaleString()}</span>
        <span class="text-xs ${c.icon} mb-0.5">boxes</span>
      </div>
      <span class="text-[10px] text-gray-400">@ 13.5 kg/box</span>
    </div>`;
  }).join('');
}

function renderHarvest(data) {
  const el = document.getElementById('harvest-summary');
  if (!el) return;
  const params = new URLSearchParams(window.location.search);
  const hasRange = params.get('start') || params.get('end');
  el.innerHTML = `
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white flex-shrink-0"
           style="background:linear-gradient(135deg,#10b981,#059669);">
        <i class="fa-solid fa-weight-hanging"></i>
      </div>
      <div>
        <span class="text-[11px] text-gray-400 font-medium uppercase tracking-wider block">
          Total Harvest${hasRange ? ' (selected range)' : ''}
        </span>
        <span class="text-2xl font-bold text-gray-800">
          ${Number(data.total_harvest_kg).toFixed(2)} <span class="text-sm font-medium text-gray-400">kg</span>
        </span>
      </div>
    </div>
    <div class="flex items-center gap-6">
      <div class="text-center">
        <span class="text-lg font-bold text-emerald-700 block">${(data.total_harvest_boxes||0).toLocaleString()}</span>
        <span class="text-[10px] text-gray-400 uppercase tracking-wider">boxes</span>
      </div>
      <div class="text-center">
        <span class="text-lg font-bold text-gray-700 block">${(data.total_harvest_count||0).toLocaleString()}</span>
        <span class="text-[10px] text-gray-400 uppercase tracking-wider">records</span>
      </div>
    </div>`;
}

function renderTable(rows) {
  const tbody = document.querySelector('#dataTable tbody');
  if (!tbody) return;
  if (!rows.length) {
    tbody.innerHTML = `<tr><td colspan="11" class="px-4 py-16 text-center">
      <i class="fa-solid fa-inbox text-gray-300 text-3xl mb-3 block"></i>
      <p class="text-gray-400 text-sm">No records found for the selected filters.</p>
      <a href="/reports" class="text-emerald-600 text-xs mt-1 inline-block hover:underline">Clear filters</a>
    </td></tr>`;
    return;
  }
  tbody.innerHTML = rows.map((row, i) => {
    const no = i + 1;
    const cells = GRADES.map(g => {
      const val = Number(row[g] || 0).toFixed(2);
      return `<td class="px-4 py-3 text-sm text-gray-700">${val} <span class="text-gray-400 text-xs">kg</span></td>`;
    }).join('');
    return `<tr class="transition-colors" style="${no%2===0?'background:#fafafa;':''}">
      <td class="px-4 py-3 text-xs text-gray-400 font-medium">${no}</td>
      <td class="px-4 py-3 text-sm font-semibold text-gray-700">${row.Farm||'N/A'}</td>
      <td class="px-4 py-3 text-sm text-gray-600">${row.date||'N/A'}</td>
      ${cells}
      <td class="px-4 py-3 text-sm font-semibold text-emerald-700">${Number(row.total_weight||0).toFixed(2)} <span class="text-emerald-500 text-xs">kg</span></td>
      <td class="px-4 py-3 text-sm font-semibold text-gray-700">${Math.floor(Number(row.total_weight||0)/13.5)} <span class="text-gray-400 text-xs">boxes</span></td>
    </tr>`;
  }).join('');

  // Client-side pagination
  const allRows = tbody.querySelectorAll('tr');
  const perPage = 8;
  let page = 1;
  const total = Math.ceil(allRows.length / perPage) || 1;
  const pageInfo = document.getElementById('pageInfo');
  const prev = document.getElementById('prevPage');
  const next = document.getElementById('nextPage');

  function show(p) {
    const s = (p-1)*perPage;
    allRows.forEach((r,i) => r.style.display = (i>=s && i<s+perPage) ? '' : 'none');
    if(pageInfo) pageInfo.textContent = `Page ${p} of ${total}`;
    if(prev) prev.disabled = p===1;
    if(next) next.disabled = p===total;
  }
  if(prev) prev.addEventListener('click', () => { if(page>1) show(--page); });
  if(next) next.addEventListener('click', () => { if(page<total) show(++page); });
  show(1);
}

function buildChart(labels, gradeData, type) {
  if (chartInstance) { chartInstance.destroy(); chartInstance = null; }
  if (!labels.length) return;
  const isBar = type==='bar', isMixed = type==='mixed';
  const visibleGrades = new URLSearchParams(window.location.search).get('grade')
    ? [new URLSearchParams(window.location.search).get('grade')]
    : GRADES;

  const datasets = visibleGrades.map(label => ({
    type: isMixed ? 'bar' : type,
    label, data: gradeData[label] || [],
    borderColor: PALETTE[label]?.border ?? '#6b7280',
    backgroundColor: (isBar||isMixed) ? (PALETTE[label]?.border??'#6b7280')+'cc' : (PALETTE[label]?.bg??'transparent'),
    borderWidth: (isBar||isMixed) ? 0 : 2.5,
    borderRadius: (isBar||isMixed) ? 4 : 0,
    pointRadius: (isBar||isMixed) ? 0 : 4,
    pointHoverRadius: (isBar||isMixed) ? 0 : 6,
    pointBackgroundColor: PALETTE[label]?.border ?? '#6b7280',
    pointBorderColor: '#fff', pointBorderWidth: 2, tension: 0.35, fill: false,
  }));

  if (isMixed) {
    datasets.push({
      type:'line', label:'Total',
      data: labels.map((_,i) => visibleGrades.reduce((s,g) => s+(gradeData[g]?.[i]??0),0)),
      borderColor:'#064e3b', backgroundColor:'transparent', borderWidth:2.5,
      pointRadius:4, pointHoverRadius:6, pointBackgroundColor:'#064e3b',
      pointBorderColor:'#fff', pointBorderWidth:2, tension:0.35, fill:false,
    });
  }

  const PX = (isBar||isMixed) ? 90 : 55;
  const wrapper = document.getElementById('chartWrapper');
  if (wrapper) wrapper.style.width = Math.max(labels.length*PX, 600)+'px';

  chartInstance = new Chart(document.getElementById('gradesChart'), {
    type: isMixed ? 'bar' : type,
    data: { labels, datasets },
    options: {
      responsive:true, maintainAspectRatio:false,
      interaction:{ mode:'index', intersect:false },
      plugins:{
        legend:{ position:'bottom', labels:{ usePointStyle:true, pointStyle:(isBar||isMixed)?'rect':'rectRounded', padding:20, font:{family:'Poppins',size:12}, color:'#374151' }},
        tooltip:{ backgroundColor:'#fff', borderColor:'#e5e7eb', borderWidth:1, titleColor:'#111827', bodyColor:'#374151', padding:12,
          callbacks:{ label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y.toFixed(2)} kg` }},
        zoom:{ zoom:{wheel:{enabled:true,speed:0.1},pinch:{enabled:true},mode:'x'}, pan:{enabled:true,mode:'x'}, limits:{x:{minRange:2}} },
      },
      scales:{
        x:{ grid:{color:'#f3f4f6',drawBorder:false}, ticks:{font:{family:'Poppins',size:11},color:'#9ca3af',maxRotation:45}, border:{display:false} },
        y:{ grid:{color:'#f3f4f6',drawBorder:false}, ticks:{font:{family:'Poppins',size:11},color:'#9ca3af',callback:v=>v+' kg'}, border:{display:false} },
      },
    },
  });
}

window.switchView = function(view) {
  const isChart = view==='chart';
  document.getElementById('chartView').style.display = isChart ? '' : 'none';
  document.getElementById('tableView').style.display = isChart ? 'none' : '';
  document.getElementById('tabChart').classList.toggle('active', isChart);
  document.getElementById('tabTable').classList.toggle('active', !isChart);
  document.getElementById('chartTypeToggle').style.display = isChart ? '' : 'none';
};

window.switchChartType = function(type) {
  activeChartType = type;
  ['line','bar','mixed'].forEach(t =>
    document.getElementById('type'+t[0].toUpperCase()+t.slice(1)).classList.toggle('active', t===type)
  );
  if (window._chartData) buildChart(window._chartData.labels, window._chartData.gradeData, type);
};

window.resetZoom = function() { if (chartInstance) chartInstance.resetZoom(); };

function startOfWeek(d) {
  const x = new Date(d);
  x.setDate(x.getDate() - ((x.getDay()+6)%7));
  x.setHours(0,0,0,0);
  return x;
}

window.applyQuickRange = function(val) {
  if (!val) return;
  const today = new Date();
  let start, end;
  switch (val) {
    case 'this_week': start=startOfWeek(today); end=today; break;
    case 'last_week': start=startOfWeek(today); start.setDate(start.getDate()-7); end=new Date(start); end.setDate(end.getDate()+6); break;
    case 'this_month': start=new Date(today.getFullYear(),today.getMonth(),1); end=today; break;
    case 'last_month': start=new Date(today.getFullYear(),today.getMonth()-1,1); end=new Date(today.getFullYear(),today.getMonth(),0); break;
    default: return;
  }
  document.getElementById('startDate').value = fmtDate(start);
  document.getElementById('endDate').value   = fmtDate(end);
  document.getElementById('filterForm').submit();
};

async function loadReports() {
  const params = new URLSearchParams(window.location.search);
  const res = await fetch('/api/reports?' + params.toString());
  if (res.status === 401) { window.location.href = '/login'; return; }
  const data = await res.json();

  renderBoxes(data.boxes_per_grade);
  renderHarvest(data);
  renderTable(data.table_rows);

  window._chartData = { labels: data.chart_labels, gradeData: data.grade_data };
  buildChart(data.chart_labels, data.grade_data, 'line');

  availableDates = data.available_dates || [];
  const selStart = params.get('start') || null;
  const selEnd   = params.get('end')   || null;

  flatpickr('#rangePicker', {
    mode:'range', dateFormat:'Y-m-d', maxDate:'today',
    defaultDate: (selStart && selEnd) ? [selStart, selEnd] : (selStart ? [selStart] : null),
    onDayCreate(dObj, dStr, fp, dayElem) {
      if (availableDates.includes(fmtDate(dayElem.dateObj))) {
        dayElem.classList.add('has-harvest');
      }
    },
    onClose(selectedDates) {
      if (!selectedDates.length) return;
      document.getElementById('startDate').value   = fmtDate(selectedDates[0]);
      document.getElementById('endDate').value     = fmtDate(selectedDates[selectedDates.length-1]);
      document.getElementById('rangeSelect').value = '';
      document.getElementById('filterForm').submit();
    },
  });
}

document.addEventListener('DOMContentLoaded', loadReports);
