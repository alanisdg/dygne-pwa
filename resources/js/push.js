export async function subscribeToPush(user, token) {

    if (!("serviceWorker" in navigator)) {
        console.log("Service worker no soportado");
        return;
    }

   const registration = await navigator.serviceWorker.ready;

// Aquí obtienes la subscripción del navegador
const subscription = await registration.pushManager.subscribe({
    userVisibleOnly: true,
    applicationServerKey: urlBase64ToUint8Array("BDrVDHBtOrdfLE0AVLDe9ntEN-fjfMyPGTji2OO7glK8mZJ2NFpmkl1g1-8WmFe1JWFNebseLiy75o74uD_37fE")
});

// Aquí la conviertes a JSON real
const subscriptionJson = subscription.toJSON();

// Enviar solo JSON plano, no el objeto completo
await axios.post("https://app.dygne.com/api/push/subscribe", subscriptionJson, {
    headers: {
        Authorization: `Bearer ${token}`,
        "Content-Type": "application/json"
    }
});

    console.log("Subscripción guardada en backend:", subscription);
}

function urlBase64ToUint8Array(base64String) {
    const padding = "=".repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, "+")
        .replace(/_/g, "/");
    const rawData = atob(base64);
    return Uint8Array.from([...rawData].map((c) => c.charCodeAt(0)));
}
