// Example: Fetch animals
function fetchAnimals() {
    fetch('api/animals')
        .then(response => response.json())
        .then(data => {
            const tableBody = document.getElementById('animals-table-body');
            tableBody.innerHTML = '';
            data.forEach(animal => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${animal.tag_number}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${animal.breed}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${animal.date_of_birth}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${animal.purchase_date}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                            animal.status === 'Active' ? 'bg-green-100 text-green-800' : 
                            animal.status === 'Recovering' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'
                        }">${animal.status}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <button class="text-blue-600 hover:text-blue-900 mr-3">View</button>
                        <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                        <button class="text-red-600 hover:text-red-900">Delete</button>
                    </td>
                `;
                tableBody.appendChild(row);
            });
        });
}

// Example: Add new animal
document.getElementById('add-animal-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = {
        tag_number: this.querySelector('input[type="text"]').value,
        breed: this.querySelector('select').value,
        date_of_birth: this.querySelector('input[type="date"]:nth-of-type(1)').value,
        purchase_date: this.querySelector('input[type="date"]:nth-of-type(2)').value,
        purchase_price: this.querySelector('input[type="number"]').value,
        status: this.querySelectorAll('select')[1].value
    };
    
    fetch('api/animals', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        alert('Animal added successfully!');
        hideModal('add-animal-modal');
        fetchAnimals(); // Refresh the list
    });
});