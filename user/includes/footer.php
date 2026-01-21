</div> <!-- End Main Container -->

<footer class="bg-gray-50 border-t border-gray-200 mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-sm text-gray-500">
                &copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.
            </div>
            <div class="flex gap-6 text-sm text-gray-500">
                <a href="#" class="hover:text-black transition">Privacy Policy</a>
                <a href="#" class="hover:text-black transition">Contact</a>
            </div>
        </div>
    </div>
</footer>

<?php include __DIR__ . '/../../includes/chat_widget.php'; ?>

</body>
</html>
