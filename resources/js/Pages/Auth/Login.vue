<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const form = useForm({
    email: '',
    password: '',
});

// THIS IS NEW: Aggressively watch for any errors from the backend
watch(() => form.errors, (errors) => {
    if (errors.password) {
        alert("❌ " + errors.password);
    } else if (errors.email) {
        alert("❌ " + errors.email);
    }
}, { deep: true });

const submit = () => {
    form.post('/login', {
        preserveScroll: true,
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="MMSU Login" />    

    <div class="min-h-screen flex items-center justify-center bg-gray-50">
        <div class="bg-white p-10 rounded-xl shadow-lg w-full max-w-md border border-gray-100">
            
            <div class="flex justify-center mb-6">
                <img
                    src="/mmsulogo.png"
                    alt="MMSU Logo"
                    class="w-50 h-50 object-contain"
                />
            </div>

            <h2 class="text-center text-green-900 font-bold text-lg mb-8 tracking-wide">
                Mariano Marcos State University
            </h2>

            <form @submit.prevent="submit" class="space-y-6">
                
                <div>
                    <input
                        v-model="form.email"
                        type="email"
                        required
                        placeholder="Enter your email..."
                        class="w-full px-5 py-3 rounded-full border focus:border-green-700 focus:ring-1 focus:ring-green-700 outline-none text-gray-600 placeholder-gray-400 transition"
                        :class="form.errors.email ? 'border-red-500 bg-red-50' : 'border-gray-300'"
                    />
                    <div v-if="form.errors.email" class="text-red-500 text-xs mt-2 ml-4 font-bold">
                        {{ form.errors.email }}
                    </div>
                </div>

                <div>
                    <input
                        v-model="form.password"
                        type="password"
                        required
                        placeholder="Enter your password..."
                        class="w-full px-5 py-3 rounded-full border focus:border-green-700 focus:ring-1 focus:ring-green-700 outline-none text-gray-600 placeholder-gray-400 transition"
                        :class="form.errors.password ? 'border-red-500 bg-red-50' : 'border-gray-300'"
                    />
                    <div v-if="form.errors.password" class="text-red-500 text-xs mt-2 ml-4 font-bold">
                        {{ form.errors.password }}
                    </div>
                </div>

                <button
                    :disabled="form.processing"
                    class="w-full bg-green-900 hover:bg-green-800 text-white font-semibold py-3 rounded-full transition duration-200 mt-2 shadow-sm disabled:opacity-50"
                >
                    <span v-if="form.processing">Logging in...</span>
                    <span v-else>Log in</span>
                </button>
            </form>
        </div>
    </div>
</template>