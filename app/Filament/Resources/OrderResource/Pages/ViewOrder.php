<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Response;
use Exception;
use Illuminate\Support\Facades\Log;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
            // Actions\Action::make('print')
            //     ->label('طباعة الفاتورة')
            //     ->icon('heroicon-o-printer')
            //     ->color('success')
                ->action(function () {
                    try {
                        // تسجيل معلومات للتdebug
                        Log::info('بدء إنشاء فاتورة للطلب: ' . $this->record->id);

                        // تحميل العلاقات مع التحقق من وجودها
                        $order = $this->record;

                        // تحميل العلاقات مع معالجة الأخطاء
                        if (method_exists($order, 'items')) {
                            $order->load([
                                'user',
                                'address',
                                'items' => function ($query) {
                                    $query->with('product'); // تحميل علاقة المنتج لكل عنصر
                                }
                            ]);

                            Log::info('تم تحميل العلاقات: ' . $order->items->count() . ' عناصر');
                        } else {
                            throw new Exception('علاقة items غير موجودة في نموذج Order');
                        }

                        // التحقق من وجود عناصر
                        if ($order->items->isEmpty()) {
                            Log::warning('الطلب لا يحتوي على أي عناصر: ' . $order->id);
                        }

                        // إنشاء PDF
                        $pdf = Pdf::loadView('orders.print-single', [
                            'order' => $order
                        ]);

                        // إرجاع الاستجابة للتنزيل
                        return Response::streamDownload(
                            function () use ($pdf) {
                                echo $pdf->stream();
                            },
                            'order-'.$order->id.'.pdf',
                            [
                                'Content-Type' => 'application/pdf',
                            ]
                        );
                    } catch (Exception $e) {
                        // تسجيل الخطأ
                        Log::error('خطأ في إنشاء الفاتورة: ' . $e->getMessage());

                        // معالجة الخطأ وإظهار رسالة للمستخدم
                        throw new \Exception('خطأ في إنشاء الفاتورة: ' . $e->getMessage());
                    }
                }),
        ];
    }
}
