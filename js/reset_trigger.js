let secretBuffer = "";
const SECRET_CODE = "DEEPRESETMODE"; // updated

document.addEventListener("keydown", function(event) {
    const key = event.key;

    // Ignore modifier keys
    if (key.length === 1) {
        secretBuffer += key.toUpperCase();

        // Keep buffer from growing too long
        if (secretBuffer.length > 50) {
            secretBuffer = secretBuffer.slice(-50);
        }

        // Check if secret sequence appears
        if (secretBuffer.includes(SECRET_CODE)) {
            secretBuffer = ""; // Reset buffer

            const confirmInput = prompt(
                "System Reset Command Detected.\nType CONFIRM to continue:"
            );

            if (confirmInput === "CONFIRM") {
                fetch("reset_system.php", { method: "POST" })
                    .then(res => res.text())
                    .then(msg => alert(msg))
                    .catch(err => alert("Error triggering reset."));
            }
        }
    }
});

