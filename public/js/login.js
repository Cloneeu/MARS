const API_URL_LOGIN = 'http://localhost:8081/mars/api/users.php?action=login';

const form = document.getElementById('login-form')

form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const user = Object.fromEntries(new FormData(form));
    console.log(user)
    const response = await login(user);
    console.log(response.usuario);
    if(!response.ok){
      alert("Usuario o contraseña incorrectos")
      
    }else{
      alert("Login Correcto");

      if(response.usuario.rol === 'admin')
        location.href ="dashboard.html"
      else 
        location.href ="products.html"


      
    }
});


const login = async (user) => {


  try {
        const response = await fetch(API_URL_LOGIN, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(user)
        });

        const data = await response.json();
        return data;

    } catch (error) {
        console.error("Error Login", error);
        return { ok: false, message: "Error de red" };
    }


}
