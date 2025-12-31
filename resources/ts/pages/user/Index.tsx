import { ReactNode } from "react";
import Authenticated from "../../layouts/Authenticated";
import { PageProps } from "@/types";
import { Link, Head } from "@inertiajs/react";
import { router } from "@inertiajs/react";
import { __ } from "../../../lang/translations";

type User = {
    id: number;
    name: string;
    email: string;
};

type Props = {
    users: User[];
};

const Index = ({ users }: PageProps<Props>) => {
    const onDelete = (user: User) => {
        const confirmDelete = window.confirm(
            `¿Estás seguro de que quieres eliminar el usuario ${user.name}?`
        );
        if (confirmDelete) {
            router.delete(route("swift-auth.users.destroy", user.id));
        }
    };

    return (
        <>
            <Head title={__("user.users")} />
            <div className="max-w-4xl mx-auto mt-10 bg-white p-6 rounded-lg shadow-md">
                <div className="flex justify-between items-center mb-4">
                    <h2 className="text-2xl font-bold text-left mb-4">
                        {__("user.users")}
                    </h2>

                    <Link
                        href={route("swift-auth.users.create")}
                        className="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                    >
                        {__("user.new_user")}
                    </Link>
                </div>
                <table className="w-full border-collapse border border-gray-200">
                    <thead>
                        <tr className="bg-gray-100">
                            <th className="border p-2">{__("user.name")}</th>
                            <th className="border p-2">{__("user.email")}</th>
                            <th className="border p-2">{__("user.actions")}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {users.length > 0 ? (
                            users.map((user) => (
                                <tr
                                    key={user.id}
                                    className="text-center hover:bg-gray-50"
                                >
                                    <td className="border p-2">{user.name}</td>
                                    <td className="border p-2">{user.email}</td>
                                    <td className="border p-2">
                                        <div className="flex justify-center space-x-2">
                                            <a
                                                href={route(
                                                    "swift-auth.users.edit",
                                                    user.id
                                                )}
                                            >
                                                <img
                                                    src="/icons/edit.svg"
                                                    className="h-8"
                                                    alt=""
                                                />
                                            </a>
                                            <button
                                                onClick={() => onDelete(user)}
                                                className="cursor-pointer"
                                            >
                                                <img
                                                    src="/icons/destroy.svg"
                                                    className="h-8 "
                                                />
                                            </button>

                                            <a
                                                href={route(
                                                    "swift-auth.users.destroy",
                                                    user.id
                                                )}
                                            ></a>
                                        </div>
                                    </td>
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td
                                    colSpan={2}
                                    className="border p-4 text-center text-gray-500"
                                >
                                    No hay usuarios autenticados.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </>
    );
};

Index.layout = (page: ReactNode) => <Authenticated>{page}</Authenticated>;

export default Index;
