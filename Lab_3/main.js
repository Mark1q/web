function fun(){
    const table = document.getElementById("table-body");

    // clear the table from previous input
    table.innerHTML = "<tr></tr>"

    const inputArray = document.getElementById("arr-input");
    const array = inputArray.value.split(', ');
    
    array.sort((a, b) => a - b);

    // check for valid formating

    for(const num of array) {
        if (parseInt(num) != num) {
            alert("Invalid format. Please try again");
            inputArray.value = ""
            return;
        }
    }

    // add the numbers to the table

    let currentRow = table.lastElementChild;
    
    for(const num of array) {
        if (currentRow.childElementCount == 5) {
            currentRow = document.createElement('tr');
            table.appendChild(currentRow);
        }

        const dataCell = document.createElement('td');
        dataCell.textContent = num;
        currentRow.appendChild(dataCell);
    }

    inputArray.value = ""
}