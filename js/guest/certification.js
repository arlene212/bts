function initCertificateUI(){
  const tooltipEl = document.getElementById('certificateInfo');
  const btn = document.getElementById('downloadCertificateBtn');
  let lastCourse = '';
  function updateTooltip(title){
    if (tooltipEl) { tooltipEl.setAttribute('title', title || '');
      if (window.bootstrap && bootstrap.Tooltip) {
        const tip = bootstrap.Tooltip.getInstance(tooltipEl) || new bootstrap.Tooltip(tooltipEl);
        tip.setContent({'.tooltip-inner': title});
      }
    }
  }
  function check(courseCode){
    if (!courseCode) return;
    fetch('../guest/handlers/certification_handler.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`action=check_eligibility&course_code=${encodeURIComponent(courseCode)}`})
      .then(r=>r.json())
      .then(d=>{
        if (!btn || !tooltipEl) return;
        if (d.success && d.eligible){
          btn.style.display = 'inline-flex';
          btn.dataset.courseCode = courseCode;
          updateTooltip('Eligible for certification');
        } else {
          btn.style.display = 'none';
          const msgs = [];
          const det = d.details || {};
          (det.missing_activities||[]).forEach(m=>{ msgs.push(`${m.type} • ${m.title}`); });
          (det.missing_quizzes||[]).forEach(q=>{ msgs.push(`Quiz • ${q.title}`); });
          if (det.hours_required!==undefined){ msgs.push(`Hours: ${Math.round(det.hours_completed||0)} / ${Math.round(det.hours_required||0)}`); }
          updateTooltip(msgs.length? ('Missing: ' + msgs.join(', ')) : 'Not eligible');
        }
      })
      .catch(()=>{ updateTooltip('Error checking eligibility'); });
  }
  document.addEventListener('courseDetailOpened', function(e){
    const cc = e.detail && e.detail.courseCode;
    lastCourse = cc;
    updateTooltip('Checking eligibility...');
    check(cc);
  });
  if (btn){
    btn.addEventListener('click', function(){
      const cc = this.dataset.courseCode || lastCourse;
      if (!cc) return;
      fetch('../guest/handlers/certification_handler.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`action=generate_certificate&course_code=${encodeURIComponent(cc)}`})
        .then(r=>r.json())
        .then(d=>{ if (d.success && d.redirect){ window.open(d.redirect, '_blank'); } })
        .catch(()=>{});
    });
  }
}

if (document.readyState === 'loading'){
  document.addEventListener('DOMContentLoaded', initCertificateUI);
} else { initCertificateUI(); }
