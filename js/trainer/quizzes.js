// Quiz Management JavaScript

document.addEventListener('DOMContentLoaded', function() {
    setupQuizManagement();
});

function setupQuizManagement() {
    // Add Quiz Modal
    const addQuizBtn = document.getElementById('addQuizBtn');
    if (addQuizBtn) {
        addQuizBtn.addEventListener('click', function() {
            openModal('addQuizModal');
        });
    }
    
    // Add Question Modal
    const addQuestionBtn = document.getElementById('addQuestionBtn');
    if (addQuestionBtn) {
        addQuestionBtn.addEventListener('click', function() {
            openModal('addQuestionModal');
        });
    }
    
    // Quiz form submission
    const addQuizForm = document.getElementById('addQuizForm');
    if (addQuizForm) {
        addQuizForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitQuizForm(this);
        });
    }
    
    // Question form submission
    const addQuestionForm = document.getElementById('addQuestionForm');
    if (addQuestionForm) {
        addQuestionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitQuestionForm(this);
        });
    }
    
    // Question type change handler
    const questionTypeSelect = document.getElementById('question_type');
    if (questionTypeSelect) {
        questionTypeSelect.addEventListener('change', function() {
            updateQuestionForm(this.value);
        });
        updateQuestionForm(questionTypeSelect.value);
    }
    
    // Add option button
    const addOptionBtn = document.getElementById('add_option_btn');
    if (addOptionBtn) {
        addOptionBtn.addEventListener('click', function() {
            addQuestionOption();
        });
    }
    
    // Edit quiz buttons
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.edit-quiz-btn');
        if (btn) {
            const quizData = JSON.parse(btn.getAttribute('data-quiz-data'));
            editQuiz(quizData);
        }
    });
    
    // View questions buttons
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.view-questions-btn');
        if (btn) {
            const quizId = btn.getAttribute('data-quiz-id');
            const quizTitle = btn.getAttribute('data-quiz-title');
            viewQuestions(quizId, quizTitle);
        }
    });
    
    // View results buttons
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.view-results-btn');
        if (btn) {
            const quizId = btn.getAttribute('data-quiz-id');
            viewResults(quizId);
        }
    });

    // Generic modal close handlers for quizzes modals
    document.addEventListener('click', function(e) {
        const closeEl = e.target.closest('.modal .close');
        if (closeEl) {
            const modal = closeEl.closest('.modal');
            if (modal) closeModal(modal);
            return;
        }
        const cancelBtn = e.target.closest('.modal .cancel-btn');
        if (cancelBtn) {
            const modal = cancelBtn.closest('.modal');
            if (modal) closeModal(modal);
        }
    });
}

function submitQuizForm(form) {
    const errors = validateQuizForm(form);
    if (errors.length) { showFormErrors('quizFormErrors', errors); return; }
    clearFormErrors('quizFormErrors');
    const formData = new FormData(form);
    formData.append('action', 'create_quiz');
    
    const submitBtn = form.querySelector('.submit-btn');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Creating...';
    submitBtn.disabled = true;
    
    fetch('../trainer/handlers/quiz_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal('addQuizModal');
            window.location.reload();
        } else {
            alert('Error creating quiz: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error creating quiz. Please try again.');
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

function submitQuestionForm(form) {
    const errors = validateQuestionForm(form);
    if (errors.length) { showFormErrors('questionFormErrors', errors); return; }
    clearFormErrors('questionFormErrors');
    const formData = new FormData(form);
    formData.append('action', 'add_question');
    
    const submitBtn = form.querySelector('.submit-btn');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Adding...';
    submitBtn.disabled = true;
    
    fetch('../trainer/handlers/quiz_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            form.reset();
            updateQuestionForm(document.getElementById('question_type').value);
            const quizSelect = document.getElementById('question_quiz');
            if (quizSelect) quizSelect.value = quizSelect.value;
            showFormSuccess('questionFormErrors', 'Question added successfully');
        } else {
            alert('Error adding question: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding question. Please try again.');
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

function updateQuestionForm(questionType) {
    const optionsSection = document.getElementById('question_options_section');
    const trueFalseSection = document.getElementById('true_false_section');
    const shortAnswerSection = document.getElementById('short_answer_section');
    const addOptionBtn = document.getElementById('add_option_btn');
    const mcRadios = document.querySelectorAll('input[name="correct_answer"]');
    const tfRadios = document.querySelectorAll('input[name="correct_answer_tf"]');
    const saInput = document.getElementById('correct_answer_sa');
    const mcTextInputs = document.querySelectorAll('#multiple_choice_options input[type="text"]');
    
    // Hide all sections
    optionsSection.style.display = 'none';
    trueFalseSection.style.display = 'none';
    shortAnswerSection.style.display = 'none';
    addOptionBtn.style.display = 'none';
    
    // Reset requirements/disabled states
    mcRadios.forEach(r => { r.required = false; r.disabled = true; });
    tfRadios.forEach(r => { r.required = false; r.disabled = true; });
    if (saInput) { saInput.required = false; saInput.disabled = true; }
    mcTextInputs.forEach(i => { i.disabled = true; });

    // Show relevant section and enable proper inputs
    switch (questionType) {
        case 'multiple_choice':
            optionsSection.style.display = 'block';
            addOptionBtn.style.display = 'block';
            mcRadios.forEach(r => { r.disabled = false; r.required = true; });
            mcTextInputs.forEach(i => { i.disabled = false; });
            break;
        case 'true_false':
            trueFalseSection.style.display = 'block';
            tfRadios.forEach(r => { r.disabled = false; r.required = true; });
            break;
        case 'short_answer':
            shortAnswerSection.style.display = 'block';
            if (saInput) { saInput.disabled = false; saInput.required = true; }
            break;
        case 'essay':
            // Essay questions don't need specific answer fields
            break;
    }
}

function validateQuizForm(form) {
    const errors = [];
    const title = form.querySelector('#quiz_title')?.value.trim();
    const course = form.querySelector('#quiz_course')?.value.trim();
    const timeLimit = form.querySelector('#quiz_time_limit')?.value;
    const maxAttempts = form.querySelector('#quiz_max_attempts')?.value;
    const passing = form.querySelector('#quiz_passing_score')?.value;
    if (!title) errors.push('Quiz title is required');
    if (!course) errors.push('Course selection is required');
    if (timeLimit && (isNaN(timeLimit) || parseInt(timeLimit, 10) <= 0)) errors.push('Time limit must be a positive number');
    if (maxAttempts && (isNaN(maxAttempts) || parseInt(maxAttempts, 10) < 1 || parseInt(maxAttempts, 10) > 10)) errors.push('Max attempts must be between 1 and 10');
    if (passing && (isNaN(passing) || parseFloat(passing) < 0 || parseFloat(passing) > 100)) errors.push('Passing score must be between 0 and 100');
    return errors;
}

function validateQuestionForm(form) {
    const errors = [];
    const quiz = form.querySelector('#question_quiz')?.value.trim();
    const text = form.querySelector('#question_text')?.value.trim();
    const type = form.querySelector('#question_type')?.value;
    const points = form.querySelector('#question_points')?.value;
    if (!quiz) errors.push('Quiz is required');
    if (!text) errors.push('Question text is required');
    if (!type) errors.push('Question type is required');
    if (points && (isNaN(points) || parseInt(points, 10) < 1 || parseInt(points, 10) > 100)) errors.push('Points must be between 1 and 100');
    if (type === 'multiple_choice') {
        const options = Array.from(form.querySelectorAll('#multiple_choice_options input[type="text"]')).map(i => i.value.trim()).filter(v => v);
        const correct = form.querySelector('input[name="correct_answer"]:checked');
        if (options.length < 2) errors.push('Multiple choice requires at least two options');
        if (!correct) errors.push('Select the correct answer');
    }
    if (type === 'true_false') {
        const tf = form.querySelector('input[name="correct_answer_tf"]:checked');
        if (!tf) errors.push('Select True or False');
    }
    if (type === 'short_answer') {
        const sa = form.querySelector('#correct_answer_sa')?.value.trim();
        if (!sa) errors.push('Short answer requires a correct answer');
    }
    return errors;
}

function showFormErrors(containerId, errors) {
    const el = document.getElementById(containerId);
    if (!el) return;
    el.classList.remove('hidden');
    el.innerHTML = '<ul>' + errors.map(e => '<li>' + e + '</li>').join('') + '</ul>';
}

function showFormSuccess(containerId, message) {
    const el = document.getElementById(containerId);
    if (!el) return;
    el.classList.remove('hidden');
    el.innerHTML = '<div class="success-message">' + message + '</div>';
    setTimeout(() => { el.classList.add('hidden'); el.innerHTML = ''; }, 2000);
}

function clearFormErrors(containerId) {
    const el = document.getElementById(containerId);
    if (!el) return;
    el.classList.add('hidden');
    el.innerHTML = '';
}

function addQuestionOption() {
    const optionsContainer = document.getElementById('multiple_choice_options');
    const optionCount = optionsContainer.querySelectorAll('.option-group').length + 1;
    
    const optionHtml = `
        <div class="option-group">
            <div class="form-group">
                <label>Option ${optionCount} <span class="required">*</span></label>
                <input type="text" name="option_${optionCount}" class="form-control" placeholder="Enter option ${optionCount}">
                <label class="radio-label">
                    <input type="radio" name="correct_answer" value="${optionCount}" required>
                    <span class="radio-mark"></span>
                    Correct Answer
                </label>
            </div>
            <button type="button" class="remove-option-btn" onclick="removeQuestionOption(this)">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    optionsContainer.insertAdjacentHTML('beforeend', optionHtml);
}

function removeQuestionOption(button) {
    button.parentElement.remove();
    // Renumber remaining options
    const optionGroups = document.querySelectorAll('#multiple_choice_options .option-group');
    optionGroups.forEach((group, index) => {
        const label = group.querySelector('label');
        const input = group.querySelector('input[type="text"]');
        const radio = group.querySelector('input[type="radio"]');
        
        const newIndex = index + 1;
        label.textContent = `Option ${newIndex} *`;
        input.name = `option_${newIndex}`;
        input.placeholder = `Enter option ${newIndex}`;
        radio.value = newIndex;
    });
}

function editQuiz(quizData) {
    // Populate edit form with quiz data
    document.getElementById('quiz_title').value = quizData.title;
    document.getElementById('quiz_course').value = quizData.course_code;
    document.getElementById('quiz_description').value = quizData.description || '';
    document.getElementById('quiz_time_limit').value = quizData.time_limit || '';
    document.getElementById('quiz_max_attempts').value = quizData.max_attempts;
    document.getElementById('quiz_passing_score').value = quizData.passing_score;
    document.getElementById('quiz_randomized').checked = quizData.is_randomized == 1;
    document.getElementById('quiz_show_answers').checked = quizData.show_correct_answers == 1;
    
    openModal('addQuizModal');
}

function viewQuestions(quizId, quizTitle) {
    document.getElementById('viewQuestionsTitle').textContent = 'Questions: ' + quizTitle;
    
    const formData = new FormData();
    formData.append('action', 'get_questions');
    formData.append('quiz_id', quizId);
    
    fetch('../trainer/handlers/quiz_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayQuestions(data.questions);
            openModal('viewQuestionsModal');
        } else {
            alert('Error loading questions: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error loading questions. Please try again.');
    });
}

function displayQuestions(questions) {
    const container = document.getElementById('viewQuestionsBody');
    
    if (questions.length === 0) {
        container.innerHTML = '<p class="empty-state">No questions found for this quiz.</p>';
        return;
    }
    
    let html = '<div class="questions-list">';
    questions.forEach((question, index) => {
        html += `
            <div class="question-item">
                <div class="question-header">
                    <span class="question-number">Question ${index + 1}</span>
                    <span class="question-points">${question.points} points</span>
                    <span class="question-difficulty difficulty-${question.difficulty}">${question.difficulty}</span>
                </div>
                <div class="question-text">${question.question_text}</div>
                <div class="question-type">Type: ${question.question_type.replace('_', ' ')}</div>
                ${question.options ? `
                    <div class="question-options">
                        <strong>Options:</strong>
                        ${JSON.parse(question.options).map(option => `
                            <div class="option ${option === question.correct_answer ? 'correct' : ''}">
                                ${option} ${option === question.correct_answer ? '<i class="fas fa-check"></i>' : ''}
                            </div>
                        `).join('')}
                    </div>
                ` : ''}
                ${question.explanation ? `
                    <div class="question-explanation">
                        <strong>Explanation:</strong> ${question.explanation}
                    </div>
                ` : ''}
            </div>
        `;
    });
    html += '</div>';
    
    container.innerHTML = html;
}

function viewResults(quizId) {
    const formData = new FormData();
    formData.append('action', 'get_results');
    formData.append('quiz_id', quizId);
    
    fetch('../trainer/handlers/quiz_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayResults(data.results);
            openModal('viewResultsModal');
        } else {
            alert('Error loading results: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error loading results. Please try again.');
    });
}

function displayResults(results) {
    const container = document.getElementById('viewResultsBody');
    
    if (results.length === 0) {
        container.innerHTML = '<p class="empty-state">No attempts found for this quiz.</p>';
        return;
    }
    
    let html = '<div class="results-list">';
    html += '<div class="results-summary">';
    html += `<div class="summary-item">
        <span class="summary-label">Total Attempts:</span>
        <span class="summary-value">${results.length}</span>
    </div>`;
    
    const passed = results.filter(r => r.score >= r.passing_score).length;
    const passRate = Math.round((passed / results.length) * 100);
    html += `<div class="summary-item">
        <span class="summary-label">Pass Rate:</span>
        <span class="summary-value">${passRate}%</span>
    </div>`;
    
    const avgScore = Math.round(results.reduce((sum, r) => sum + parseFloat(r.score), 0) / results.length);
    html += `<div class="summary-item">
        <span class="summary-label">Average Score:</span>
        <span class="summary-value">${avgScore}%</span>
    </div>`;
    html += '</div>';
    
    html += '<div class="results-table">';
    html += '<table class="table">';
    html += '<thead><tr>';
    html += '<th>Student</th>';
    html += '<th>Score</th>';
    html += '<th>Pass/Fail</th>';
    html += '<th>Attempt #</th>';
    html += '<th>Time Spent</th>';
    html += '<th>Completed</th>';
    html += '</tr></thead>';
    html += '<tbody>';
    
    results.forEach(result => {
        const passed = result.score >= result.passing_score;
        const timeSpent = result.time_spent ? formatTime(result.time_spent) : 'N/A';
        const completedDate = new Date(result.completed_at).toLocaleDateString();
        
        html += `<tr>
            <td>${result.first_name} ${result.last_name}</td>
            <td>${result.score}%</td>
            <td><span class="badge ${passed ? 'badge-success' : 'badge-danger'}">${passed ? 'Passed' : 'Failed'}</span></td>
            <td>${result.attempt_number}</td>
            <td>${timeSpent}</td>
            <td>${completedDate}</td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    html += '</div></div>';
    
    container.innerHTML = html;
}

function formatTime(seconds) {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    return `${minutes}m ${remainingSeconds}s`;
}