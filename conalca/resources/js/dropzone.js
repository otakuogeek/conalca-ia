import Dropzone from "dropzone";
import $ from "jquery";

Dropzone.autoDiscover = false;

let dropzoneInstance = null;
let myDropzoneInstance = null;

const dropzoneElement = document.getElementById("dropzone");
if (dropzoneElement) {
    dropzoneInstance = new Dropzone("#dropzone", {
        dictDefaultMessage: "Sube aquí tu documento",
        acceptedFiles: ".pdf",
        addRemoveLinks: true,
        dictRemoveFile: "Borrar archivo",
        maxFilesize: 10,
        maxFiles: 10,
        uploadMultiple: true,
        disablePreviews: true,
    });

    dropzoneInstance.on("addedfile", (file) => {
        $("#uploaded-files").append(`
        <button class="delete-file-button flex flex-col items-center text-[#898989] text-xs font-normal leading-[1.03125rem]">
            <span class="${file.name}">
                <svg height="40px" width="40px" viewBox="0 0 512 512" fill="#000000">
                    <path d="M347.746,346.204c-8.398-0.505-28.589,0.691-48.81,4.533c-11.697-11.839-21.826-26.753-29.34-39.053"/>
                </svg>
            </span>
            ${file.name.slice(0, 8)}
        </button>
        `);

        $(".delete-file-button").on("click", function () {
            const fileName = $(this).find("span").attr("class");
            if (dropzoneInstance) {
                const fileToRemove = dropzoneInstance.files.find(
                    (file) => file.name === fileName
                );
                if (fileToRemove) {
                    dropzoneInstance.removeFile(fileToRemove);
                }
            }
            $(this).remove();
        });
    });

    dropzoneInstance.on("processing", (file) => {
        if (file.status === "uploading") {
            const previewElement = document.createElement("li");
            previewElement.textContent = file.name;

            const pendingList = document.querySelector("#pending-list");
            if (pendingList) {
                pendingList.appendChild(previewElement);
            }
        }
    });

    dropzoneInstance.on("success", (file) => {
        const pendingElements = document.querySelectorAll("#pending-list li");
        pendingElements.forEach((element) => {
            if (element.textContent.includes(file.name) && file.status === "success") {
                element.remove();
            }
        });
    });

    dropzoneInstance.on("error", (file, message) => {
        console.log("Dropzone error:", message);
    });
} else {
    console.log("Dropzone element (#dropzone) not found. Skipping initialization.");
}

const dropzoneBoxElement = document.getElementById("dropzone-box");
if (dropzoneBoxElement) {
    myDropzoneInstance = new Dropzone("#dropzone-box", {
        dictDefaultMessage: "Sube aquí tu documento",
        acceptedFiles: ".pdf",
        addRemoveLinks: true,
        dictRemoveFile: "Borrar archivo",
        maxFilesize: 10,
        maxFiles: 10,
        uploadMultiple: true,
        disablePreviews: true,
    });

    myDropzoneInstance.on("addedfile", (file) => {
        $("#uploaded-documents").append(`
        <button class="delete-document-button flex flex-col items-center text-[#898989] text-xs font-normal leading-[1.03125rem] ml-[1.38rem]">
            <span class="${file.name}">
                <svg height="40px" width="40px" viewBox="0 0 512 512" fill="#000000">
                    <path d="M347.746,346.204c-8.398-0.505-28.589,0.691-48.81,4.533c-11.697-11.839-21.826-26.753-29.34-39.053"/>
                </svg>
            </span>
            ${file.name.slice(0, 8)}
        </button>
        `);

        $(".delete-document-button").on("click", function () {
            const fileName = $(this).find("span").attr("class");
            if (myDropzoneInstance) {
                const fileToRemove = myDropzoneInstance.files.find(
                    (file) => file.name === fileName
                );
                if (fileToRemove) {
                    myDropzoneInstance.removeFile(fileToRemove);
                }
            }
            $(this).remove();
        });
    });

    myDropzoneInstance.on("processing", (file) => {
        if (file.status === "uploading") {
            const previewElement = document.createElement("li");
            previewElement.textContent = file.name;

            const documentList = document.querySelector("#document-list");
            if (documentList) {
                documentList.appendChild(previewElement);
            }
        }
    });

    myDropzoneInstance.on("success", (file) => {
        const pendingElements = document.querySelectorAll("#document-list li");
        pendingElements.forEach((element) => {
            if (element.textContent.includes(file.name) && file.status === "success") {
                element.remove();
            }
        });
    });

    myDropzoneInstance.on("error", (file, message) => {
        console.log("MyDropzone error:", message);
    });
} else {
    console.log("Dropzone-box element (#dropzone-box) not found. Skipping initialization.");
}
