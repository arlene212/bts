function initializeSwitches() {
  const trainerSwitchButtons = document.querySelectorAll('#trainers .switch-btn');
  const traineeSwitchButtons = document.querySelectorAll('#trainees .switch-btn');
  const trainerSwitchInner = document.getElementById('trainerSwitchInner');
  const traineeSwitchInner = document.getElementById('traineeSwitchInner');
  if (trainerSwitchButtons.length > 0) {
    updateSwitchPosition(trainerSwitchButtons, trainerSwitchInner);
    trainerSwitchButtons.forEach(btn => {
      btn.addEventListener('click', function() {
        const targetTab = this.getAttribute('data-tab');
        const parentSection = this.closest('.tab-content');
        trainerSwitchButtons.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        updateSwitchPosition(trainerSwitchButtons, trainerSwitchInner);
        const tabInners = parentSection.querySelectorAll('.tab-inner');
        tabInners.forEach(tab => tab.classList.remove('active'));
        const targetElement = parentSection.querySelector(`#${targetTab}`);
        if (targetElement) { targetElement.classList.add('active'); }
      });
    });
  }
  if (traineeSwitchButtons.length > 0) {
    updateSwitchPosition(traineeSwitchButtons, traineeSwitchInner);
    traineeSwitchButtons.forEach(btn => {
      btn.addEventListener('click', function() {
        const targetTab = this.getAttribute('data-tab');
        const parentSection = this.closest('.tab-content');
        traineeSwitchButtons.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        updateSwitchPosition(traineeSwitchButtons, traineeSwitchInner);
        const tabInners = parentSection.querySelectorAll('.tab-inner');
        tabInners.forEach(tab => tab.classList.remove('active'));
        const targetElement = parentSection.querySelector(`#${targetTab}`);
        if (targetElement) { targetElement.classList.add('active'); }
      });
    });
  }
}

function updateSwitchPosition(buttons, switchInner) {
  const activeBtn = Array.from(buttons).find(btn => btn.classList.contains('active'));
  if (activeBtn && switchInner) {
    const btnRect = activeBtn.getBoundingClientRect();
    const containerRect = activeBtn.parentElement.getBoundingClientRect();
    switchInner.style.width = `${btnRect.width}px`;
    switchInner.style.height = `${btnRect.height}px`;
    switchInner.style.transform = `translateX(${btnRect.left - containerRect.left}px)`;
  }
}