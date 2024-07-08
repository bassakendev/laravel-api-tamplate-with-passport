<?php

namespace App\Enums;

enum ProfessionalSpecialityEnum: string
{
    case HEALTH_PROFESSIONAL = 'Health professionals';
    case LEGAL_PROFESSIONAL = 'Legal professionals';
    case SUPPORT_SERVICES_PROFESSIONAL = 'Support Services Professionals';
    case HOSTING_AND_PROTECTION_PROFESSIONAL = 'Hosting And Protection Professionals';
    case EDUCATION_AND_PREVENTION_PROFESSIONAL = 'Education And Prevention Professionals';
}
