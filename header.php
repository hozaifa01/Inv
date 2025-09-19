<?php
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
  ini_set('session.cookie_secure', 1);
}
// بدء الجلسة إذا لم تكن مفعلة
 error_reporting(0);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="robots" content="no-follow, no-index, max-image-preview:large">
  <meta name="generator" content="hozaifa01">
  <meta name="apple-mobile-web-app-capable" content="yes">

  <!-- أيقونة الموقع -->
  <link rel="icon" href="a49.png">
  <link rel="shortcut icon" href="a49.png">
  
  <!-- ملفات CSS -->
   <link rel="stylesheet" href="dist/bootstrap-icons-1.11.0/bootstrap-icons.min.css">
        <link rel="stylesheet" href="jquery-ui.css">

  <link rel="stylesheet" href="datatables.css">
  <link rel="stylesheet" href="dist/Font-Awesome/CSS/font-awesome.min.css">
         <link rel="stylesheet" href="datatables.min.css">
          <link rel="stylesheet" href="style.css">
  <!-- مكتبات JavaScript -->
  <script src="jquery-3.7.0.min.js"></script>
  <script src="datatables.min.js"></script>
  <script src="jquery-ui.min.js"></script>
  <script src="chart.js"></script>
  <script src="pdfmake.js"></script>
  <script src="vfs_fonts.js"></script>
    <script src="jspdf.min.js"></script>
  <script src="jspdf.umd.min.js"></script>
    <script src="html2canvas.min.js"></script>
  <script src="bootstrap.min.js"></script>
  <script src="bootstrap.bundle.min.js"></script>
  <script src="popper.min.js"></script>
  <script src="qrcode.min.js"></script>
  <!-- تفعيل الوضع المظلم -->
  <script>
    $(document).ready(function () {
      const body = $('body');
      const themeKey = 'theme';

      if (localStorage.getItem(themeKey) === 'dark') {
        body.addClass('dark-mode');
      }

      $('#theme-toggler').on('click', function () {
        body.toggleClass('dark-mode');
        localStorage.setItem(themeKey, body.hasClass('dark-mode')? 'dark': 'light');
      });
    });
    $(function() {
  $(".btn").addClass("ui-button");

  $(".ui-dialog").addClass("modal-content");

  // تحويل التبويبات إلى نمط Bootstrap
  $(".nav").addClass("ui-tabs");
  $(".ui-tabs-panel").addClass("tab-content");
})
  </script>
  <script>
    function convertBootstrapToJQuery() {
    const bootstrapClasses = {
        'container': 'container',
        'row': 'row',
        'col': 'col',
        'card': 'ui-widget',
        'card-body': 'ui-widget-content',
        'card-header': 'ui-widget-header',
        'btn': 'ui-button',
        'btn-primary': 'ui-button-primary',
        'form-control': 'ui-input',
        'input-group': 'ui-widget',
        'input-group-text': 'ui-widget-header',
    };

    Object.keys(bootstrapClasses).forEach((bootstrapClass) => {
        const elements = document.getElementsByClassName(bootstrapClass);
        Array.from(elements).forEach((element) => {
            element.classList.add(bootstrapClasses[bootstrapClass]);
        });
    });
}

// استدعاء الدالة بعد تحميل الصفحة
document.addEventListener('DOMContentLoaded', convertBootstrapToJQuery);
  // مثال باستخدام Gulp لدمج وتصغير الملفات
const gulp = require('gulp');
const concat = require('gulp-concat');
const uglify = require('gulp-uglify');
const cleanCSS = require('gulp-clean-css');
const sourcemaps = require('gulp-sourcemaps');
const rename = require('gulp-rename');
const gulpIf = require('gulp-if');
const crypto = require('crypto');

// إعدادات عامة
const isProduction = process.env.NODE_ENV === 'production';
const paths = {
  scripts: 'src/**/*.js',
  styles: 'src/**/*.css',
  phps: 'src/**/*.php',
  distJS: 'dist/js',
  distCSS: 'dist/css'
};

// توليد اسم مشفر للملف
function generateHashName(baseName, ext) {
  const hash = crypto.randomBytes(4).toString('hex');
  return `${baseName}.${hash}.${ext}`;
}

// JavaScript
gulp.task('scripts', function() {
  const outputName = isProduction ? generateHashName('bundle', 'min.js') : 'bundle.min.js';
  return gulp.src(paths.scripts)
    .pipe(sourcemaps.init())
    .pipe(concat(outputName))
    .pipe(gulpIf(isProduction, uglify()))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest(paths.distJS));
});
gulp.task('phps', function() {
  const outputName = isProduction ? generateHashName('bundle', 'min.js') :
  'bundle.min.php';
  return gulp.src(paths.scripts)
    .pipe(sourcemaps.init())
    .pipe(concat(outputName))
    .pipe(gulpIf(isProduction, uglify()))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest(paths.distJS));
});

// CSS
gulp.task('styles', function() {
  const outputName = isProduction ? generateHashName('bundle', 'min.css') : 'bundle.min.css';
  return gulp.src(paths.styles)
    .pipe(concat(outputName))
    .pipe(gulpIf(isProduction, cleanCSS()))
    .pipe(gulp.dest(paths.distCSS));
});

// مراقبة التغييرات
gulp.task('watch', function() {
  gulp.watch(paths.scripts, gulp.series('scripts'));
  gulp.watch(paths.styles, gulp.series('styles'));
});

// المهمة الافتراضية
gulp.task('default', gulp.parallel('scripts', 'styles'));
    </script>
    
    <script>
        $(document).ready(function() {
            // تأثيرات الواجهة
            $('.card').hover(
                function() {
                    $(this).css('transform', 'translateY(-5px)');
                },
                function() {
                    $(this).css('transform', 'translateY(0)');
                }
            );
        });
    </script>