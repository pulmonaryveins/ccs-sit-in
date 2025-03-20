// Tab switching functionality
document.querySelectorAll('.filter-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        // Remove active class from all tabs
        document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
        // Add active class to clicked tab
        this.classList.add('active');
        // Hide all record containers
        document.querySelectorAll('.records-container').forEach(container => {
            container.classList.remove('active');
        });
        // Show the target container
        const targetId = this.getAttribute('data-target');
        document.getElementById(targetId).classList.add('active');
        // Toggle action buttons based on active tab
        if (targetId === 'student-records') {
            document.getElementById('student-records-actions').style.display = 'flex';
            document.getElementById('sitin-records-actions').style.display = 'none';
        } else {
            document.getElementById('student-records-actions').style.display = 'none';
            document.getElementById('sitin-records-actions').style.display = 'flex';
        }
        // Clear search input when switching tabs
        document.getElementById('searchInput').value = '';
    });
});

// Add Student Modal Functions
function openAddStudentModal() {
    // Reset form fields
    document.getElementById('addStudentForm').reset();
    document.getElementById('addStudentModal').classList.add('active');
}

function closeAddStudentModal() {
    document.getElementById('addStudentModal').classList.remove('active');
}

function addStudent() {
    // Get form data
    const idno = document.getElementById('add_idno').value;
    const firstname = document.getElementById('add_firstname').value;
    const lastname = document.getElementById('add_lastname').value;
    const username = document.getElementById('add_username').value;
    const yearLevel = document.getElementById('add_year_level').value;
    const course = document.getElementById('add_course_id').value;
    const password = document.getElementById('add_password').value;
    const confirmPassword = document.getElementById('add_confirm_password').value;
    
    // Validate form
    if (!idno || !firstname || !lastname || !username || !yearLevel || !course || !password || !confirmPassword) {
        alert('Please fill all required fields');
        return;
    }
    
    // Validate ID number format
    if (!/^\d{8}$/.test(idno)) {
        alert('ID Number must be exactly 8 digits');
        return;
    }
    
    if (password !== confirmPassword) {
        alert('Passwords do not match');
        return;
    }
    
    // Show loading state
    const addButton = document.querySelector('#addStudentModal .btn-primary');
    const originalText = addButton.textContent;
    addButton.textContent = 'Adding...';
    addButton.disabled = true;
    
    // Send data to server
    fetch('../controller/add_student.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            idno: idno,
            firstname: firstname,
            lastname: lastname,
            username: username,
            year_level: yearLevel,
            course_id: course,
            password: password
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            closeAddStudentModal();
            alert('Student added successfully!');
            // Refresh the page to show the new student
            location.reload();
        } else {
            alert('Error: ' + data.message);
            // Reset button state
            addButton.textContent = originalText;
            addButton.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing your request. Please check the console for details.');
        // Reset button state
        addButton.textContent = originalText;
        addButton.disabled = false;
    });
}

// Functions for resetting all sessions
function confirmResetAllSessions() {
    document.getElementById('resetAllSessionsModal').classList.add('active');
}

function closeResetAllSessionsModal() {
    document.getElementById('resetAllSessionsModal').classList.remove('active');
}

function resetAllSessions() {
    // Show loading state or disable button
    const resetButton = document.querySelector('#resetAllSessionsModal .btn-danger');
    const originalText = resetButton.textContent;
    resetButton.textContent = 'Processing...';
    resetButton.disabled = true;
    
    fetch('../controller/reset_all_sessions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeResetAllSessionsModal();
            alert(`Success! ${data.count} students' sessions have been reset to 30.`);
            location.reload(); // Reload to show updated values
        } else {
            alert('Error: ' + data.message);
            // Reset button state
            resetButton.textContent = originalText;
            resetButton.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing your request.');
        // Reset button state
        resetButton.textContent = originalText;
        resetButton.disabled = false;
    });
}

// Functions for clearing all sit-in records
function confirmClearAllRecords() {
    document.getElementById('clearAllRecordsModal').classList.add('active');
}

function closeClearAllRecordsModal() {
    document.getElementById('clearAllRecordsModal').classList.remove('active');
}

function clearAllRecords() {
    // Show loading state or disable button
    const clearButton = document.querySelector('#clearAllRecordsModal .btn-danger');
    const originalText = clearButton.textContent;
    clearButton.textContent = 'Processing...';
    clearButton.disabled = true;
    
    fetch('../controller/clear_all_records.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeClearAllRecordsModal();
            alert(`Success! ${data.count} sit-in records have been deleted.`);
            // Refresh the sit-in records
            refreshSitInRecords();
            sitinData = []; // Clear the local data
            setupSitinPagination(); // Rebuild pagination
        } else {
            alert('Error: ' + data.message);
            // Reset button state
            clearButton.textContent = originalText;
            clearButton.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing your request.');
        // Reset button state
        clearButton.textContent = originalText;
        clearButton.disabled = false;
    });
}

// Initialize action buttons visibility on page load
document.addEventListener('DOMContentLoaded', function() {
    setupStudentsPagination();
    setupSitinPagination();
    // Set initial visibility of action buttons based on active tab
    if (document.getElementById('student-records').classList.contains('active')) {
        document.getElementById('student-records-actions').style.display = 'flex';
        document.getElementById('sitin-records-actions').style.display = 'none';
    } else {
        document.getElementById('student-records-actions').style.display = 'none';
        document.getElementById('sitin-records-actions').style.display = 'flex';
    }
});

// ... existing code for pagination, search functionality, etc. ...

function closeAddStudentModal() {
    document.getElementById('addStudentModal').classList.remove('active');
}

// Edit Student Modal Functions
function openEditModal(student) {
    // Populate the form with student data
    document.getElementById('edit_id').value = student.id;
    document.getElementById('edit_idno').value = student.idno;
    document.getElementById('edit_firstname').value = student.firstname;
    document.getElementById('edit_lastname').value = student.lastname;
    document.getElementById('edit_year_level').value = student.year_level || student.year || "1";
    document.getElementById('edit_course_id').value = student.course_id || student.course || "1";
    
    // Show the modal
    document.getElementById('editModal').classList.add('active');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
}

function saveStudentChanges() {
    // Get form data
    const formData = new FormData(document.getElementById('editStudentForm'));
    
    // Show loading state on button
    const saveButton = document.querySelector('#editModal .btn-primary');
    const originalText = saveButton.textContent;
    saveButton.textContent = 'Saving...';
    saveButton.disabled = true;
    
    // Send data to server
    fetch('../controller/update_student.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeEditModal();
            
            // Show success message
            const successMessage = document.createElement('div');
            successMessage.className = 'success-message';
            successMessage.textContent = 'Student information updated successfully!';
            successMessage.style.position = 'fixed';
            successMessage.style.top = '20px';
            successMessage.style.left = '50%';
            successMessage.style.transform = 'translateX(-50%)';
            successMessage.style.padding = '10px 20px';
            successMessage.style.backgroundColor = '#dcfce7';
            successMessage.style.color = '#16a34a';
            successMessage.style.borderRadius = '8px';
            successMessage.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
            successMessage.style.zIndex = '1000';
            
            document.body.appendChild(successMessage);
            
            // Remove message after 3 seconds
            setTimeout(() => {
                successMessage.style.opacity = '0';
                successMessage.style.transition = 'opacity 0.5s ease';
                setTimeout(() => {
                    document.body.removeChild(successMessage);
                }, 500);
            }, 3000);
            
            // Refresh the page to show updated data
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to update student information'));
            
            // Reset button state
            saveButton.textContent = originalText;
            saveButton.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing your request.');
        
        // Reset button state
        saveButton.textContent = originalText;
        saveButton.disabled = false;
    });
}

// Delete Student Modal Functions
function confirmDelete(id, name) {
    // Set the student name and ID in the modal
    document.getElementById('deleteStudentName').textContent = name;
    document.getElementById('deleteStudentId').value = id;
    
    // Show the modal
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
}

function deleteStudent() {
    const studentId = document.getElementById('deleteStudentId').value;
    
    // Show loading state
    const deleteButton = document.querySelector('#deleteModal .btn-danger');
    const originalText = deleteButton.textContent;
    deleteButton.textContent = 'Deleting...';
    deleteButton.disabled = true;
    
    // Send delete request
    fetch('../controller/delete_student.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            id: studentId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeDeleteModal();
            
            // Show success message
            const successMessage = document.createElement('div');
            successMessage.className = 'success-message';
            successMessage.textContent = 'Student deleted successfully!';
            successMessage.style.position = 'fixed';
            successMessage.style.top = '20px';
            successMessage.style.left = '50%';
            successMessage.style.transform = 'translateX(-50%)';
            successMessage.style.padding = '10px 20px';
            successMessage.style.backgroundColor = '#fee2e2';
            successMessage.style.color = '#dc2626';
            successMessage.style.borderRadius = '8px';
            successMessage.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
            successMessage.style.zIndex = '1000';
            
            document.body.appendChild(successMessage);
            
            // Remove message after 3 seconds
            setTimeout(() => {
                successMessage.style.opacity = '0';
                successMessage.style.transition = 'opacity 0.5s ease';
                setTimeout(() => {
                    document.body.removeChild(successMessage);
                }, 500);
            }, 3000);
            
            // Refresh the page to show updated data
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to delete student'));
            
            // Reset button state
            deleteButton.textContent = originalText;
            deleteButton.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing your request.');
        
        // Reset button state
        deleteButton.textContent = originalText;
        deleteButton.disabled = false;
    });
}

// Reset Sessions Modal Functions
function confirmResetSessions(idno, name) {
    // Set the student name and ID in the modal
    document.getElementById('resetStudentName').textContent = name;
    document.getElementById('resetStudentIdno').value = idno;
    
    // Show the modal
    document.getElementById('resetSessionsModal').classList.add('active');
}

function closeResetModal() {
    document.getElementById('resetSessionsModal').classList.remove('active');
}

function resetSessions() {
    const studentIdno = document.getElementById('resetStudentIdno').value;
    
    // Show loading state
    const resetButton = document.querySelector('#resetSessionsModal .btn-primary');
    const originalText = resetButton.textContent;
    resetButton.textContent = 'Resetting...';
    resetButton.disabled = true;
    
    // Send reset request
    fetch('../controller/reset_student_sessions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            idno: studentIdno
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeResetModal();
            
            // Show success message
            const successMessage = document.createElement('div');
            successMessage.className = 'success-message';
            successMessage.textContent = 'Student sessions reset successfully!';
            successMessage.style.position = 'fixed';
            successMessage.style.top = '20px';
            successMessage.style.left = '50%';
            successMessage.style.transform = 'translateX(-50%)';
            successMessage.style.padding = '10px 20px';
            successMessage.style.backgroundColor = '#e0f2fe';
            successMessage.style.color = '#0369a1';
            successMessage.style.borderRadius = '8px';
            successMessage.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
            successMessage.style.zIndex = '1000';
            
            document.body.appendChild(successMessage);
            
            // Remove message after 3 seconds
            setTimeout(() => {
                successMessage.style.opacity = '0';
                successMessage.style.transition = 'opacity 0.5s ease';
                setTimeout(() => {
                    document.body.removeChild(successMessage);
                }, 500);
            }, 3000);
            
            // Refresh the page to show updated data
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to reset student sessions'));
            
            // Reset button state
            resetButton.textContent = originalText;
            resetButton.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing your request.');
        
        // Reset button state
        resetButton.textContent = originalText;
        resetButton.disabled = false;
    });
}

// Functions for resetting all sessions
function confirmResetAllSessions() {
    document.getElementById('resetAllSessionsModal').classList.add('active');
}

function closeResetAllSessionsModal() {
    document.getElementById('resetAllSessionsModal').classList.remove('active');
}

function resetAllSessions() {
    // Show loading state or disable button
    const resetButton = document.querySelector('#resetAllSessionsModal .btn-danger');
    const originalText = resetButton.textContent;
    resetButton.textContent = 'Processing...';
    resetButton.disabled = true;
    
    fetch('../controller/reset_all_sessions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeResetAllSessionsModal();
            alert(`Success! ${data.count} students' sessions have been reset to 30.`);
            location.reload(); // Reload to show updated values
        } else {
            alert('Error: ' + data.message);
            // Reset button state
            resetButton.textContent = originalText;
            resetButton.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing your request.');
        // Reset button state
        resetButton.textContent = originalText;
        resetButton.disabled = false;
    });
}

// Functions for clearing all sit-in records
function confirmClearAllRecords() {
    document.getElementById('clearAllRecordsModal').classList.add('active');
}

function closeClearAllRecordsModal() {
    document.getElementById('clearAllRecordsModal').classList.remove('active');
}

function clearAllRecords() {
    // Show loading state or disable button
    const clearButton = document.querySelector('#clearAllRecordsModal .btn-danger');
    const originalText = clearButton.textContent;
    clearButton.textContent = 'Processing...';
    clearButton.disabled = true;
    
    fetch('../controller/clear_all_records.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeClearAllRecordsModal();
            alert(`Success! ${data.count} sit-in records have been deleted.`);
            // Refresh the sit-in records
            refreshSitInRecords();
            sitinData = []; // Clear the local data
            setupSitinPagination(); // Rebuild pagination
        } else {
            alert('Error: ' + data.message);
            // Reset button state
            clearButton.textContent = originalText;
            clearButton.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing your request.');
        // Reset button state
        clearButton.textContent = originalText;
        clearButton.disabled = false;
    });
}

// Initialize action buttons visibility on page load
document.addEventListener('DOMContentLoaded', function() {
    setupStudentsPagination();
    setupSitinPagination();
    // Set initial visibility of action buttons based on active tab
    if (document.getElementById('student-records').classList.contains('active')) {
        document.getElementById('student-records-actions').style.display = 'flex';
        document.getElementById('sitin-records-actions').style.display = 'none';
    } else {
        document.getElementById('student-records-actions').style.display = 'none';
        document.getElementById('sitin-records-actions').style.display = 'flex';
    }
});
