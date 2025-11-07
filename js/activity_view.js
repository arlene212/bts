document.addEventListener("DOMContentLoaded", () => {
    const uploadSection = document.getElementById("uploadSection");
    if (!uploadSection) return;

    const uploadArea = document.getElementById("uploadArea");
    const fileInput = document.getElementById("activityFileInput");
    const filePreview = document.getElementById("activityFilePreview");
    const submitBtn = document.getElementById("activitySubmitBtn");
    const studentComment = document.getElementById("studentComment");
    const submissionSuccess = document.getElementById("submissionSuccess");
    const activityId = uploadSection.dataset.activityId;

    // Open file dialog on click
    uploadArea.addEventListener("click", () => fileInput.click());

    // Handle file selection
    fileInput.addEventListener("change", (e) => {
        const file = e.target.files[0];
        if (file) {
            handleFile(file);
        }
    });

    // Handle drag and drop
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('active');
    });
    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('active');
    });
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('active');
        const file = e.dataTransfer.files[0];
        if (file) {
            fileInput.files = e.dataTransfer.files;
            handleFile(file);
        }
    });

    function handleFile(file) {
        // Basic validation (can be expanded)
        if (file.size > 20 * 1024 * 1024) { // 20MB limit
            alert("File is too large. Maximum size is 20MB.");
            return;
        }

        filePreview.innerHTML = `
            <div class="file-preview-item">
                <i class="fas fa-file-alt"></i>
                <div class="file-details">
                    <strong>${file.name}</strong>
                    <span>(${(file.size / 1024 / 1024).toFixed(2)} MB)</span>
                </div>
                <button class="remove-file-btn">&times;</button>
            </div>
        `;
        filePreview.classList.remove('hidden');
        submitBtn.disabled = false;

        // Remove file
        filePreview.querySelector('.remove-file-btn').addEventListener('click', () => {
            fileInput.value = '';
            filePreview.classList.add('hidden');
            submitBtn.disabled = true;
        });
    }

    // Handle submission
    submitBtn.addEventListener("click", () => {
        if (!fileInput.files[0]) {
            alert("Please select a file to upload.");
            return;
        }

        const formData = new FormData();
        formData.append('activity_id', activityId);
        formData.append('submission_file', fileInput.files[0]);
        formData.append('comment', studentComment.value.trim());

        submitBtn.disabled = true;
        submitBtn.textContent = "Submitting...";

        fetch('../php/submit_activity.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                uploadSection.style.display = 'none';
                submissionSuccess.classList.remove('hidden');
                // Optionally, redirect or show submission details after a delay
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                alert('Submission failed: ' + data.error);
                submitBtn.disabled = false;
                submitBtn.textContent = "Submit Assignment";
            }
        })
        .catch(error => {
            console.error('Error submitting activity:', error);
            alert('An error occurred during submission.');
            submitBtn.disabled = false;
            submitBtn.textContent = "Submit Assignment";
        });
    });
});