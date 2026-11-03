function showSection(sectionId, element) {

    const sections = document.querySelectorAll(".content-section");

    sections.forEach(section => {
        section.style.display = "none";
    });

    document.getElementById(sectionId).style.display = "block";

    const links = document.querySelectorAll(".menu-link");

    links.forEach(link => {
        link.classList.remove("active");
    });

    element.classList.add("active");

}