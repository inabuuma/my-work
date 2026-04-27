document.getElementById("assignmentForm").addEventListener("submit", function(e) {

    let valid = true;

    // Clear previous errors
    document.querySelectorAll(".error").forEach(el => el.innerText = "");

    let name = document.getElementById("student_name").value.trim();
    let id = document.getElementById("student_id").value.trim();
    let subject = document.getElementById("subject").value;
    let title = document.getElementById("assignment_title").value.trim();
    let date = document.getElementById("due_date").value;
    let marks = document.getElementById("marks").value;

    // Name validation
    if (!/^[A-Za-z ]{3,}$/.test(name)) {
        document.getElementById("nameError").innerText = "Enter valid name (min 3 letters)";
        valid = false;
    }

    // Student ID validation
    if (!/^[0-9]{5}$/.test(id)) {
        document.getElementById("idError").innerText = "ID must be exactly 5 digits";
        valid = false;
    }

    // Subject validation
    if (subject === "") {
        document.getElementById("subjectError").innerText = "Select a subject";
        valid = false;
    }

    // Title validation
    if (title.length < 5) {
        document.getElementById("titleError").innerText = "Title must be at least 5 characters";
        valid = false;
    }

    // Date validation
    let today = new Date().toISOString().split("T")[0];
    if (date < today) {
        document.getElementById("dateError").innerText = "Date cannot be in the past";
        valid = false;
    }

    // Marks validation
    if (marks < 0 || marks > 100) {
        document.getElementById("marksError").innerText = "Marks must be between 0 and 100";
        valid = false;
    }

    if (!valid) {
        e.preventDefault(); // STOP form submission
    }

});
let assignments = [
    {title: "Algebra", subject: "Mathematics"},
    {title: "Biology", subject: "Science"},
    {title: "Essay Writing", subject: "English"},
    {title: "Physics", subject: "Science"},
    {title: "World History", subject: "History"}
];

// Display all on load
window.onload = function() {
    displayAssignments(assignments);
};

function displayAssignments(list) {
    let ul = document.getElementById("assignmentList");
    ul.innerHTML = "";

    list.forEach(item => {
        let li = document.createElement("li");
        li.textContent = item.title + " (" + item.subject + ")";
        ul.appendChild(li);
    });
}

function filterAssignments() {
    let selected = document.querySelector('input[name="filter"]:checked').value;

    if (selected === "all") {
        displayAssignments(assignments);
    } else {
        let filtered = assignments.filter(a => a.subject === selected);
        displayAssignments(filtered);
    }
}