import "../bootstrap";

import ReactDOM from "react-dom/client";

function App() {
    return (
        <div>
            <div class="col-md-8">
                <h1>Hello World</h1>
            </div>
        </div>
    );
}

const root = ReactDOM.createRoot(document.getElementById("app"));
root.render(<App />);
