// document.addEventListener('DOMContentLoaded', () => {
//     console.log('Fetching user data...');
    
//     fetch('http://localhost:5500/api/users') // Ensure correct API URL
//         .then(response => {
//             console.log('Response status:', response.status); 
//             if (!response.ok) {
//                 throw new Error(`HTTP error! Status: ${response.status}`);
//             }
//             return response.json();
//         })
//         .then(data => {
//             console.log('Data received:', data); 
//             const tbody = document.querySelector('#usersTable tbody');
//             tbody.innerHTML = ''; // Clear existing content

//             if (data.length === 0) {
//                 console.log('No users found');
//                 tbody.innerHTML = '<tr><td colspan="3">No users found</td></tr>';
//                 return;
//             }

//             data.forEach(user => {
//                 console.log('Adding user:', user); 
//                 const row = document.createElement('tr');
//                 row.innerHTML = `
//                     <td>${user.username}</td>
//                     <td>${user.email}</td>
//                     <td>${user.is_verified ? 'Yes' : 'No'}</td>
//                 `;
//                 tbody.appendChild(row);
//             });
//         })
//         .catch(error => { 
//             console.error('Error fetching users:', error);
//             alert('Failed to load user data. Check the console for details.');
//         });
// });



document.addEventListener('DOMContentLoaded', () => {
    console.log('Fetching user data...');
    
    // Fetch user data from your API
    fetch('http://localhost:5500/api/users') // Update API URL if needed
      .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
          throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        console.log('Data received:', data);
        const tbody = document.querySelector('#usersTable tbody');
        tbody.innerHTML = ''; // Clear any existing content
  
        if (data.length === 0) {
          console.log('No users found');
          tbody.innerHTML = '<tr><td colspan="4">No users found</td></tr>';
          return;
        }
  
        // Populate table rows with user data
        data.forEach(user => {
          console.log('Adding user:', user);
          const row = document.createElement('tr');
          // Save the user id for later deletion
          row.setAttribute('data-user-id', user.id);
  
          row.innerHTML = `
            <td>${user.username}</td>
            <td>${user.email}</td>
            <td>${user.is_verified ? 'Yes' : 'No'}</td>
            <td class="checkbox-column">
              <input type="checkbox" class="delete-checkbox">
            </td>
          `;
          tbody.appendChild(row);
        });
      })
      .catch(error => {
        console.error('Error fetching users:', error);
        alert('Failed to load user data. Check the console for details.');
      });
    
    // Get button and action area references
    const enableDeleteBtn = document.getElementById('enableDelete');
    const applyDeleteBtn = document.getElementById('applyDelete');
    const cancelDeleteBtn = document.getElementById('cancelDelete');
    const actionButtons = document.getElementById('actionButtons');
  
    // Enable delete mode: show checkboxes and action buttons
    enableDeleteBtn.addEventListener('click', () => {
      // Show the checkbox column header by explicitly setting display to table-cell
      const headerCheckbox = document.querySelector('thead .checkbox-column');
      if (headerCheckbox) headerCheckbox.style.display = 'table-cell';
      
      // For each row, show the checkbox cell
      const rows = document.querySelectorAll('#usersTable tbody tr');
      rows.forEach(row => {
        const checkboxTd = row.querySelector('.checkbox-column');
        if (checkboxTd) {
          checkboxTd.style.display = 'table-cell';
        }
      });
      
      // Show Apply/Cancel buttons and hide the initial Delete button
      actionButtons.style.display = 'block';
      enableDeleteBtn.style.display = 'none';
    });
    
    // Cancel delete mode: hide checkboxes and action buttons, reset checkboxes
    cancelDeleteBtn.addEventListener('click', () => {
      // Hide the checkbox column header
      const headerCheckbox = document.querySelector('thead .checkbox-column');
      if (headerCheckbox) headerCheckbox.style.display = 'none';
      
      // Hide each row's checkbox cell and uncheck the checkbox
      const rows = document.querySelectorAll('#usersTable tbody tr');
      rows.forEach(row => {
        const checkboxTd = row.querySelector('.checkbox-column');
        if (checkboxTd) {
          const checkbox = checkboxTd.querySelector('.delete-checkbox');
          if (checkbox) checkbox.checked = false;
          checkboxTd.style.display = 'none';
        }
      });
      
      // Hide action buttons and show the Delete button again
      actionButtons.style.display = 'none';
      enableDeleteBtn.style.display = 'block';
    });
    
    // Apply deletion for selected rows
    applyDeleteBtn.addEventListener('click', () => {
      const selectedRows = [];
      const rows = document.querySelectorAll('#usersTable tbody tr');
      
      rows.forEach(row => {
        const checkbox = row.querySelector('.delete-checkbox');
        if (checkbox && checkbox.checked) {
          selectedRows.push(row);
        }
      });
      
      if (selectedRows.length === 0) {
        alert('No rows selected for deletion.');
        return;
      }
      
      if (!confirm('Are you sure you want to delete the selected user(s)?')) {
        return;
      }
      
      // For each selected row, send a DELETE request and remove the row if successful
      selectedRows.forEach(row => {
        const userId = row.getAttribute('data-user-id');
        fetch(`http://localhost:5500/api/users/${userId}`, {
          method: 'DELETE'
        })
          .then(response => {
            if (!response.ok) {
              throw new Error(`Failed to delete user with ID ${userId}`);
            }
            // Remove the row from the table upon successful deletion
            row.remove();
          })
          .catch(error => {
            console.error('Error deleting user:', error);
            alert(`Error deleting user with ID ${userId}`);
          });
      });
      
      // Exit delete mode: hide checkboxes and reset buttons
      const headerCheckbox = document.querySelector('thead .checkbox-column');
      if (headerCheckbox) headerCheckbox.style.display = 'none';
      actionButtons.style.display = 'none';
      enableDeleteBtn.style.display = 'block';
    });
  });
  
