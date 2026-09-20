{{-- The office entry modal, mounted by the student view page outside the tabs.
     .svx is the student view's design system (components/student/view-premium),
     which this component's markup is styled by. --}}
<div class="svx">
    <x-student.topup-modal :direction="$direction" :payment-methods="$paymentMethods" :balance="$balance" />
</div>
