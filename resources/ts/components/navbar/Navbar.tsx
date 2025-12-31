import { Link, usePage } from "@inertiajs/react";
import { useState } from "react";
import { __ } from "../../../lang/translations";
import { LanguageSwitcher } from "../LanguageSwitcher";

export function Navbar({ user }: { user?: { name: string } }) {
    const [isOpen, setIsOpen] = useState(false);
    const { locale } = usePage().props as any;

    return (
        <nav className="bg-gray-900 text-white">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="flex items-center justify-between py-4">
                    <div className="flex space-x-6">
                        <Link href="/" className="hover:text-gray-300">
                            {__("nav.home")}
                        </Link>
                        <Link
                            href={route("swift-auth.users.index")}
                            className="hover:text-gray-300"
                        >
                            {__("nav.users")}
                        </Link>
                        <Link
                            href={route("swift-auth.roles.index")}
                            className="hover:text-gray-300"
                        >
                            {__("nav.roles")}
                        </Link>
                    </div>

                    {/* Botón menú hamburguesa (móvil) */}
                    {/* <div className="sm:hidden">
                        <button onClick={() => setIsOpen(!isOpen)}>
                            {isOpen ? <X size={24} /> : <Menu size={24} />}
                        </button>
                    </div> */}

                    <div className="hidden space-x-4 sm:flex">
                        {user ? (
                            <>
                                <span className="px-4 py-2">{user.name}</span>
                                <LanguageSwitcher
                                    currentLocale={locale}
                                    className="mr-2"
                                />
                                <form
                                    method="GET"
                                    action={route("swift-auth.logout")}
                                >
                                    <button
                                        type="submit"
                                        className="rounded bg-red-500 px-4 py-2 hover:bg-red-600"
                                    >
                                        {__("auth.logout_button")}
                                    </button>
                                </form>
                            </>
                        ) : (
                            <>
                                <LanguageSwitcher
                                    currentLocale={locale}
                                    className="mr-2"
                                />
                                <Link
                                    href="/login"
                                    className="rounded bg-blue-500 px-4 py-2 hover:bg-blue-600"
                                >
                                    {__("auth.login_button")}
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </div>

            {isOpen && (
                <div className="bg-gray-800 py-2 sm:hidden">
                    <div className="flex flex-col items-center space-y-4">
                        <Link href="/" className="hover:text-gray-300">
                            {__("nav.home")}
                        </Link>
                        <Link href="/users" className="hover:text-gray-300">
                            {__("nav.users")}
                        </Link>
                        <Link href="/roles" className="hover:text-gray-300">
                            {__("nav.roles")}
                        </Link>

                        {user ? (
                            <>
                                <span className="py-2">{user.name}</span>
                                <LanguageSwitcher currentLocale={locale} />
                                <form method="POST" action="/logout">
                                    <button
                                        type="submit"
                                        className="rounded bg-red-500 px-4 py-2 hover:bg-red-600"
                                    >
                                        {__("auth.logout_button")}
                                    </button>
                                </form>
                            </>
                        ) : (
                            <>
                                <LanguageSwitcher currentLocale={locale} />
                                <Link
                                    href="/login"
                                    className="rounded bg-blue-500 px-4 py-2 hover:bg-blue-600"
                                >
                                    {__("auth.login_button")}
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            )}
        </nav>
    );
}
