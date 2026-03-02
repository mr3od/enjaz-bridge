import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';

type Props = {
    phone?: string;
};

export default function ResetPassword({ phone = '+967' }: Props) {
    return (
        <AuthLayout
            title="Reset password"
            description="Enter your phone, OTP code, and new password"
        >
            <Head title="Reset password" />

            <Form
                action="/phone/reset-password"
                method="post"
                resetOnSuccess={['password', 'password_confirmation']}
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="phone">Phone number</Label>
                            <Input
                                id="phone"
                                type="tel"
                                name="phone"
                                autoComplete="tel"
                                defaultValue={phone}
                                className="mt-1 block w-full"
                                dir="ltr"
                            />
                            <InputError
                                message={errors.phone}
                                className="mt-2"
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="code">OTP code</Label>
                            <Input
                                id="code"
                                type="text"
                                name="code"
                                autoComplete="one-time-code"
                                className="mt-1 block w-full"
                                placeholder="123456"
                                maxLength={6}
                                dir="ltr"
                            />
                            <InputError
                                message={errors.code}
                                className="mt-2"
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">Password</Label>
                            <Input
                                id="password"
                                type="password"
                                name="password"
                                autoComplete="new-password"
                                className="mt-1 block w-full"
                                autoFocus
                                placeholder="Password"
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">
                                Confirm password
                            </Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                autoComplete="new-password"
                                className="mt-1 block w-full"
                                placeholder="Confirm password"
                            />
                            <InputError
                                message={errors.password_confirmation}
                                className="mt-2"
                            />
                        </div>

                        <Button
                            type="submit"
                            className="mt-4 w-full"
                            disabled={processing}
                            data-test="reset-password-button"
                        >
                            {processing && <Spinner />}
                            Reset password
                        </Button>
                    </div>
                )}
            </Form>
        </AuthLayout>
    );
}
