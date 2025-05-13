document.addEventListener('DOMContentLoaded', () => {
    console.log('Fetching new user data...'); 

    fetch('http://localhost:5500/api/new_users') // Ensure correct API URL
        .then(response => {
            console.log('Response status:', response.status); 
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Data received:', data); 
            const tbody = document.querySelector('#newUsersTable tbody');
            tbody.innerHTML = ''; // Clear existing content
            
            if (data.length === 0) {
                console.log('No new users found');
                tbody.innerHTML = '<tr><td colspan="3">No new users found</td></tr>';
                return;
            }
            
            data.forEach(user => {
                console.log('Adding new user:', user); 
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${user.username}</td>
                    <td>${user.email}</td>
                    <td>${new Date(user.created_at).toLocaleDateString()}</td>

                `;
                tbody.appendChild(row);
            });
        })
        .catch(error => {
            console.error('Error fetching new users:', error); 
            alert('Failed to load new user data. Check the console for details.');
        });
});
