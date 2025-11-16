import Header from "./Header";
import Navigation from "./Navigation";
import Sidebar from "./Sidebar";

export default function AppLayout({ children }) {
    return (
        <div className="flex h-screen bg-gray-100">
            <Sidebar />

            <div className="flex-1 flex flex-col">
                <Header />
                <Navigation />

                <main className="p-6">
                    {children}
                </main>
            </div>
        </div>
    );
}
