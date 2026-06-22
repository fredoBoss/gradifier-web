const GRADE_COLORS = {
  '25BCP': {bg:'#d1fae5',text:'#065f46'}, '30BCP': {bg:'#dbeafe',text:'#1e40af'},
  '33BCP': {bg:'#fef3c7',text:'#92400e'}, '30TR':  {bg:'#ede9fe',text:'#5b21b6'},
  'IF36TR':{bg:'#fce7f3',text:'#9d174d'}, 'IF38TR':{bg:'#ccfbf1',text:'#134e4a'},
};

function fmtDate(d) {
  return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');
}

function renderHarvest(data, params) {
  const el = document.getElementById('harvest-summary');
  if (!el) return;
  const hasRange = params.get('start') || params.get('end');
  el.innerHTML = `
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white flex-shrink-0" style="background:linear-gradient(135deg,#10b981,#059669);">
        <i class="fa-solid fa-weight-hanging"></i>
      </div>
      <div>
        <span class="text-[11px] text-gray-400 font-medium uppercase tracking-wider block">Total Harvest${hasRange?' (selected range)':''}</span>
        <span class="text-2xl font-bold text-gray-800">${Number(data.total_kg).toFixed(2)} <span class="text-sm font-medium text-gray-400">kg</span></span>
      </div>
    </div>
    <div class="flex items-center gap-6">
      <div class="text-center">
        <span class="text-lg font-bold text-emerald-700 block">${(data.total_boxes||0).toLocaleString()}</span>
        <span class="text-[10px] text-gray-400 uppercase tracking-wider">boxes</span>
      </div>
      <div class="text-center">
        <span class="text-lg font-bold text-gray-700 block">${(data.total_count||0).toLocaleString()}</span>
        <span class="text-[10px] text-gray-400 uppercase tracking-wider">records</span>
      </div>
    </div>`;
}

function populateSelect(id, values, selected) {
  const el = document.getElementById(id);
  if (!el) return;
  const existing = el.querySelector('option[value=""]');
  values.forEach(v => {
    if (el.querySelector(`option[value="${v}"]`)) return;
    const opt = document.createElement('option');
    opt.value = v; opt.textContent = v;
    if (v === selected) opt.selected = true;
    el.appendChild(opt);
  });
}

function renderTable(rows) {
  const tbody = document.querySelector('#logTable tbody');
  if (!tbody) return;
  let totalWeight = 0;

  if (!rows.length) {
    tbody.innerHTML = `<tr><td colspan="8" class="px-4 py-16 text-center">
      <i class="fa-solid fa-inbox text-gray-300 text-3xl mb-3 block"></i>
      <p class="text-gray-400 text-sm">No records found.</p>
    </td></tr>`;
    const tfoot = document.querySelector('#logTable tfoot');
    if (tfoot) tfoot.style.display = 'none';
    return;
  }

  tbody.innerHTML = rows.map((row, i) => {
    const no = i+1;
    const color = GRADE_COLORS[row.Classes] || {bg:'#f3f4f6',text:'#374151'};
    const conf = row.conf ? (parseFloat(row.conf)*100).toFixed(1) : '0.0';
    const confColor = conf>=80 ? '#059669' : conf>=60 ? '#d97706' : '#dc2626';
    const wt = parseFloat(row.weight||0);
    totalWeight += wt;
    return `<tr class="transition-colors" style="${no%2===0?'background:#fafafa;':''}">
      <td class="px-4 py-3 text-xs text-gray-400 font-medium">${no}</td>
      <td class="px-4 py-3 text-sm font-semibold text-gray-700 whitespace-nowrap">${row.Farm||''}</td>
      <td class="px-4 py-3">
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap" style="background:${color.bg};color:${color.text};">${row.Classes||''}</span>
      </td>
      <td class="px-4 py-3 text-sm text-gray-600">${row.classes_name||''}</td>
      <td class="px-4 py-3 text-sm text-gray-600">${row.size||''}</td>
      <td class="px-4 py-3 text-sm text-gray-700">${wt.toFixed(1)} <span class="text-gray-400 text-xs">g</span></td>
      <td class="px-4 py-3">
        <span class="text-xs font-semibold px-2 py-0.5 rounded-md" style="background:${confColor}22;color:${confColor};">${conf}%</span>
      </td>
      <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">${row.timestamp||''}</td>
    </tr>`;
  }).join('');

  // Update footer total
  const footWeight = document.getElementById('foot-weight');
  if (footWeight) footWeight.textContent = `${totalWeight.toFixed(1)} g (${(totalWeight/1000).toFixed(2)} kg)`;

  // Pagination
  const allRows = tbody.querySelectorAll('tr');
  const perPage = 12;
  let page = 1;
  const total = Math.ceil(allRows.length/perPage)||1;
  const pageInfo = document.getElementById('pageInfo');
  const prev = document.getElementById('prevPage');
  const next = document.getElementById('nextPage');
  function show(p) {
    const s=(p-1)*perPage;
    allRows.forEach((r,i)=>r.style.display=(i>=s&&i<s+perPage)?'':'none');
    if(pageInfo) pageInfo.textContent=`Page ${p} of ${total}`;
    if(prev) prev.disabled=p===1;
    if(next) next.disabled=p===total;
  }
  if(prev) prev.addEventListener('click',()=>{if(page>1)show(--page);});
  if(next) next.addEventListener('click',()=>{if(page<total)show(++page);});
  show(1);
}

function startOfWeek(d) {
  const x=new Date(d); x.setDate(x.getDate()-((x.getDay()+6)%7)); x.setHours(0,0,0,0); return x;
}

window.applyQuickRange = function(val) {
  if(!val) return;
  const today=new Date(); let start,end;
  switch(val){
    case 'this_week': start=startOfWeek(today);end=today;break;
    case 'last_week': start=startOfWeek(today);start.setDate(start.getDate()-7);end=new Date(start);end.setDate(end.getDate()+6);break;
    case 'this_month': start=new Date(today.getFullYear(),today.getMonth(),1);end=today;break;
    case 'last_month': start=new Date(today.getFullYear(),today.getMonth()-1,1);end=new Date(today.getFullYear(),today.getMonth(),0);break;
    default:return;
  }
  document.getElementById('startDate').value=fmtDate(start);
  document.getElementById('endDate').value=fmtDate(end);
  document.getElementById('filterForm').submit();
};

async function loadLogs() {
  const params = new URLSearchParams(window.location.search);
  const res = await fetch('/api/logs?' + params.toString());
  if (res.status===401) { window.location.href='/login'; return; }
  const data = await res.json();

  renderHarvest(data, params);
  populateSelect('sizeSelect',   data.distinct_sizes,   params.get('size')   || '');
  populateSelect('fingerSelect', data.distinct_fingers, params.get('finger') || '');
  renderTable(data.rows);

  const selStart = params.get('start')||null;
  const selEnd   = params.get('end')  ||null;

  flatpickr('#rangePicker', {
    mode:'range', dateFormat:'Y-m-d', maxDate:'today',
    defaultDate:(selStart&&selEnd)?[selStart,selEnd]:(selStart?[selStart]:null),
    onDayCreate(dObj,dStr,fp,dayElem){
      if((data.available_dates||[]).includes(fmtDate(dayElem.dateObj))){
        dayElem.classList.add('has-harvest');
      }
    },
    onClose(selectedDates){
      if(!selectedDates.length)return;
      document.getElementById('startDate').value=fmtDate(selectedDates[0]);
      document.getElementById('endDate').value=fmtDate(selectedDates[selectedDates.length-1]);
      document.getElementById('rangeSelect').value='';
      document.getElementById('filterForm').submit();
    },
  });
}

document.addEventListener('DOMContentLoaded', loadLogs);
