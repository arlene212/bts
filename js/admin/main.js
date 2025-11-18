function safeInitialize() {
  const fns = [
    typeof initializeModalButtons !== 'undefined' ? initializeModalButtons : null,
    typeof setupGlobalEventListeners !== 'undefined' ? setupGlobalEventListeners : null,
    typeof initializeCompetencyHandlers !== 'undefined' ? initializeCompetencyHandlers : null,
    typeof initializeEditCompetencyHandlers !== 'undefined' ? initializeEditCompetencyHandlers : null,
    typeof setupTabNavigation !== 'undefined' ? setupTabNavigation : null,
    typeof setupDashboardCards !== 'undefined' ? setupDashboardCards : null,
    typeof initializeSwitches !== 'undefined' ? initializeSwitches : null,
    typeof setupDynamicBatchLoading !== 'undefined' ? setupDynamicBatchLoading : null,
    typeof setupUserManagement !== 'undefined' ? setupUserManagement : null,
    typeof setupFormValidation !== 'undefined' ? setupFormValidation : null,
    typeof setupCourseEditing !== 'undefined' ? setupCourseEditing : null,
    typeof setupAjaxPagination !== 'undefined' ? setupAjaxPagination : null,
    typeof setupEnrollmentActions !== 'undefined' ? setupEnrollmentActions : null,
    typeof setupGuestEnrollment !== 'undefined' ? setupGuestEnrollment : null,
  ];
  fns.forEach(fn => { if (typeof fn === 'function') { try { fn(); } catch (_) {} } });
}

document.addEventListener('DOMContentLoaded', function () {
  initializeSearch('trainer', window.initialTrainerSearch || '');
  initializeSearch('trainee', window.initialTraineeSearch || '');
  initializeSearch('guest', window.initialGuestSearch || '');
  initializeSearch('enrollment', window.initialEnrollmentSearch || '');
  if (typeof activateTabFromUrl === 'function') { try { activateTabFromUrl(); } catch (_) {} }
  safeInitialize();
});