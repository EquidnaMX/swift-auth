import { Head, useForm } from "@inertiajs/react";
import { FormEvent, ReactNode } from "react";
import Authenticated from "../../layouts/Authenticated";
import { __ } from "../../../lang/translations";

type EditFormProps = {
    user: {
        id: number;
        name: string;
        email: string;
    };
};

const EditForm = ({ user }: EditFormProps) => {
    const { data, setData, put, processing, errors } = useForm({
        name: user.name,
        email: user.email,
    });

    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        put(route("swift-auth.users.update", user.id));
    };

    const handleCancel = () => {
        window.history.back();
    };

    return (
        <>
            <Head title={__("user.edit")} />
            <div className="max-w-md mx-auto mt-10 bg-white p-6 rounded-lg shadow-md">
                <h2 className="text-2xl font-bold text-center mb-4">
                    {__("user.edit")}
                </h2>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium">
                            {__("auth.name")}
                        </label>
                        <input
                            type="text"
                            name="name"
                            value={data.name}
                            onChange={(e) => setData("name", e.target.value)}
                            className="w-full border rounded px-3 py-2 mt-1"
                            required
                        />
                        {errors.name && (
                            <p className="text-gray-500 text-sm">
                                {errors.name}
                            </p>
                        )}
                    </div>

                    <div>
                        <label className="block text-sm font-medium">
                            {__("auth.email")}
                        </label>
                        <input
                            type="email"
                            name="email"
                            value={data.email}
                            onChange={(e) => setData("email", e.target.value)}
                            className="w-full border rounded px-3 py-2 mt-1"
                            required
                        />
                        {errors.email && (
                            <p className="text-gray-500 text-sm">
                                {errors.email}
                            </p>
                        )}
                    </div>

                    <div className="flex justify-between items-center">
                        <button
                            type="button"
                            className="bg-transparent hover:bg-gray-500 text-gray-700 font-semibold hover:text-white py-2 px-4 border border-gray-500 hover:border-transparent rounded"
                            onClick={handleCancel}
                        >
                            {__("auth.cancel")}
                        </button>

                        <button
                            type="submit"
                            className="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600"
                            disabled={processing}
                        >
                            {processing ? __("auth.loading") : __("user.save")}
                        </button>
                    </div>
                </form>
            </div>
        </>
    );
};

EditForm.layout = (page: ReactNode) => <Authenticated>{page}</Authenticated>;

export default EditForm;
