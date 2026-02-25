<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import toastr from 'toastr'
import 'toastr/build/toastr.min.css'

const form = useForm({
    email: '',
    password: '',
});

watch(() => form.errors, (errors) => {
    if (Object.keys(errors).length > 0) {
        toastr.error('Wrong email or password.')
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

    <div class="min-h-screen flex items-center justify-center bg-gray-50 p-4">
        <div class="bg-white p-10 rounded-xl shadow-lg w-full max-w-md border border-gray-100">
            
            <div class="flex justify-center mb-6">
                <img src="/mmsulogo.png" alt="MMSU Logo" class="w-40 h-40 object-contain" />
            </div>

            <h2 class="text-center text-green-900 font-bold text-lg mb-10 tracking-wide">
                Mariano Marcos State University
            </h2>

            <form @submit.prevent="submit" class="space-y-8">
                
                <div class="relative">
                    <input
                        v-model="form.email"
                        type="email"
                        id="email"
                        required
                        placeholder=" " 
                        class="peer w-full px-5 py-3 rounded-lg border bg-transparent focus:border-green-700 focus:ring-1 focus:ring-green-700 outline-none text-gray-700 transition-all placeholder-transparent"
                        :class="form.errors.email ? 'border-red-500' : 'border-gray-300'"
                    />
                    <label 
                        for="email"
                        class="absolute left-4 px-1 bg-white text-gray-400 text-sm transition-all pointer-events-none
                               top-3 peer-placeholder-shown:top-3 peer-placeholder-shown:text-base
                               peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-green-700
                               not-placeholder-shown:-top-2.5 not-placeholder-shown:text-xs"
                        :class="form.email ? '-top-2.5 text-xs text-green-700' : ''"
                    >
                        Enter your Email
                    </label>
                    <div v-if="form.errors.email" class="text-red-500 text-xs mt-1 ml-2 font-bold">
                        {{ form.errors.email }}
                    </div>
                </div>

                <div class="relative">
                    <input
                        v-model="form.password"
                        type="password"
                        id="password"
                        required
                        placeholder=" "
                        class="peer w-full px-5 py-3 rounded-lg border bg-transparent focus:border-green-700 focus:ring-1 focus:ring-green-700 outline-none text-gray-700 transition-all placeholder-transparent"
                        :class="form.errors.password ? 'border-red-500' : 'border-gray-300'"
                    />
                    <label 
                        for="password"
                        class="absolute left-4 px-1 bg-white text-gray-400 text-sm transition-all pointer-events-none
                               top-3 peer-placeholder-shown:top-3 peer-placeholder-shown:text-base
                               peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-green-700
                               not-placeholder-shown:-top-2.5 not-placeholder-shown:text-xs"
                        :class="form.password ? '-top-2.5 text-xs text-green-700' : ''"
                    >
                        Enter your Password
                    </label>
                    <div v-if="form.errors.password" class="text-red-500 text-xs mt-1 ml-2 font-bold">
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

<style scoped>
/* Force label up for browser autofill */
input:-webkit-autofill + label {
    top: -0.6rem !important;
    font-size: 0.75rem !important;
    background-color: white;
}
</style>