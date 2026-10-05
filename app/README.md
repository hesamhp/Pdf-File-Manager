# 📱 اپلیکیشن مخزن PDF

## ⚡ روش ساده (بدون کدنویسی - 5 دقیقه)

### سایت‌های آنلاین ساخت APK:

**1. WebIntoApp.com (پیشنهادی)**
   - برو به [webintoapp.com](https://webintoapp.com)
   - لینک سایتت رو بزن
   - تنظیمات: Splash ✅ | Offline Page ✅ | File Upload ✅
   - دانلود APK رایگان

**2. GoNative.io**
   - برو به [gonative.io](https://gonative.io)
   - لینک سایت → تنظیمات → دانلود APK

**3. AppsGeyser.com**
   - برو به [appsgeyser.com](https://www.appsgeyser.com)
   - Website to App → لینک بزن → ساخت APK

---

## 🛠 روش حرفه‌ای (Android Studio)

### پیش‌نیاز:
- Android Studio نصب شده
- JDK 11+

### قدم‌ها:

1. Android Studio باز کن → **New Project → Empty Activity**

2. Package name: `com.pdfvault.app`

3. فایل‌ها رو کپی کن:
   ```
   app/src/main/java/com/pdfvault/app/MainActivity.java  ← از فایل MainActivity.java
   app/src/main/res/layout/activity_main.xml              ← از فایل activity_main.xml
   app/src/main/res/drawable/splash_logo_bg.xml
   app/src/main/res/drawable/retry_btn_bg.xml
   app/src/main/res/drawable/ic_pdf.xml
   app/src/main/res/drawable/ic_wifi_off.xml
   app/src/main/res/values/styles.xml
   app/src/main/res/xml/network_security_config.xml
   app/src/main/AndroidManifest.xml
   ```

4. **مهم!** توی `MainActivity.java` لینک سایتت رو عوض کن:
   ```java
   private static final String SITE_URL = "https://YOUR-DOMAIN.com/index.php";
   ```

5. **Build → Build Bundle/APK → Build APK**

6. فایل APK از مسیر `app/build/outputs/apk/debug/` برداشت میشه

---

## 📋 قابلیت‌های اپ:

| قابلیت | وضعیت |
|--------|--------|
| 🎨 Splash Screen بنفش با لوگو | ✅ |
| 🌐 WebView تمام صفحه | ✅ |
| 📴 صفحه قطع اینترنت + دکمه تلاش مجدد | ✅ |
| 📤 آپلود فایل (تک و چندتایی) | ✅ |
| ⬇️ دانلود فایل PDF | ✅ |
| 🔙 دکمه بک اندروید | ✅ |
| 🔒 HTTPS پشتیبانی | ✅ |
| 📱 پرتره ثابت | ✅ |
| 🍪 ذخیره Session/Cookie | ✅ |

---

## ⚠️ نکات مهم:

- سایتت **باید HTTPS** باشه (SSL داشته باشه)
- حتماً لینک رو توی `MainActivity.java` عوض کن
- برای انتشار در گوگل‌پلی باید APK رو Sign کنی
