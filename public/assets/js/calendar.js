class DatePicker {
  constructor(containerSel, hiddenInputSel){
    this.container = document.querySelector(containerSel);
    this.hiddenInput = document.querySelector(hiddenInputSel);
    this.viewYear = null; this.viewMonth = null;
    this.selectedISO = this.hiddenInput.value || null;
    this.MONTHS = ["January","February","March","April","May","June","July","August","September","October","November","December"];
    this.YEAR_START=1950; this.YEAR_END=2050;
    this.renderSkeleton(); this.fillYearSelect(); this.bindEvents();
    const t=new Date();
    if(this.selectedISO){ const [y,m]=this.selectedISO.split('-').map(Number); this.setView(y,m-1);}
    else this.setView(t.getFullYear(),t.getMonth());
  }
  pad(n){return n<10?"0"+n:""+n;} toISO(y,m,d){return `${y}-${this.pad(m+1)}-${this.pad(d)}`;}
  renderSkeleton(){this.container.innerHTML=`
    <div class="calendar-header">
      <button type="button" class="nav-btn prev-year">«</button>
      <button type="button" class="nav-btn prev-month">‹</button>
      <div class="month-label"></div>
      <button type="button" class="nav-btn next-month">›</button>
      <button type="button" class="nav-btn next-year">»</button>
      <select class="year-select"></select>
      <button type="button" class="nav-btn today-btn">●</button>
    </div>
    <div class="calendar-week"><div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div></div>
    <div class="calendar-grid"></div>`; this.monthLabel=this.container.querySelector('.month-label'); this.yearSelect=this.container.querySelector('.year-select'); this.grid=this.container.querySelector('.calendar-grid');}
  fillYearSelect(){for(let y=this.YEAR_START;y<=this.YEAR_END;y++){const o=document.createElement('option');o.value=y;o.textContent=y;this.yearSelect.appendChild(o);}}
  setView(y,m){this.viewYear=y;this.viewMonth=m;this.monthLabel.textContent=`${this.MONTHS[m]} ${y}`;this.yearSelect.value=String(y);this.renderGrid();}
  daysInMonth(y,m){return new Date(y,m+1,0).getDate();}
  renderGrid(){this.grid.innerHTML='';const first=new Date(this.viewYear,this.viewMonth,1),start=first.getDay(),total=this.daysInMonth(this.viewYear,this.viewMonth);const prevM=(this.viewMonth+11)%12,prevY=this.viewMonth===0?this.viewYear-1:this.viewYear,pdays=this.daysInMonth(prevY,prevM);for(let i=0;i<start;i++)this.makeDay(prevY,prevM,pdays-start+i+1,true);for(let d=1;d<=total;d++)this.makeDay(this.viewYear,this.viewMonth,d,false);const rem=this.grid.children.length%7;if(rem){const add=7-rem,nm=(this.viewMonth+1)%12,ny=this.viewMonth===11?this.viewYear+1:this.viewYear;for(let d=1;d<=add;d++)this.makeDay(ny,nm,d,true);}}
  makeDay(y,m,d,other){const el=document.createElement('div');el.className='day'+(other?' other':'');const iso=this.toISO(y,m,d),today=new Date(),todayISO=this.toISO(today.getFullYear(),today.getMonth(),today.getDate());if(iso===todayISO)el.classList.add('today');if(this.selectedISO&&iso===this.selectedISO)el.classList.add('selected');el.textContent=d;el.addEventListener('click',()=>{this.selectedISO=iso;this.hiddenInput.value=iso;if(this.onSelect) this.onSelect(iso);if(other) this.setView(y,m);else this.renderGrid();});this.grid.appendChild(el);}
  bindEvents(){this.container.querySelector('.prev-year').addEventListener('click',()=>this.setView(this.viewYear-1,this.viewMonth));this.container.querySelector('.next-year').addEventListener('click',()=>this.setView(this.viewYear+1,this.viewMonth));this.container.querySelector('.prev-month').addEventListener('click',()=>this.setView(this.viewMonth===0?this.viewYear-1:this.viewYear,(this.viewMonth+11)%12));this.container.querySelector('.next-month').addEventListener('click',()=>this.setView(this.viewMonth===11?this.viewYear+1:this.viewYear,(this.viewMonth+1)%12));this.yearSelect.addEventListener('change',()=>this.setView(parseInt(this.yearSelect.value,10),this.viewMonth));this.container.querySelector('.today-btn').addEventListener('click',()=>{const t=new Date();this.setView(t.getFullYear(),t.getMonth());this.selectedISO=this.toISO(t.getFullYear(),t.getMonth(),t.getDate());this.hiddenInput.value=this.selectedISO;if(this.onSelect) this.onSelect(this.selectedISO);});}
}
window.DatePicker=DatePicker;
