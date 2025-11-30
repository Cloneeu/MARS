const API_URL = 'http://localhost:8081/mars/api/users.php?action=register';

const form = document.getElementById('register-form');

form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const data = Object.fromEntries(new FormData(form));
    console.log(data)
    const response = await registerUser(data);
    console.log(response);
    if(response.ok){
      alert("Usuario Registrado")
      location.reload();
    }
});

const registerUser = async (usuario) => {
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(usuario)
        });

        const data = await response.json();
        return data;

    } catch (error) {
        console.error("Error registrando usuario:", error);
        return { ok: false, message: "Error de red" };
    }
};