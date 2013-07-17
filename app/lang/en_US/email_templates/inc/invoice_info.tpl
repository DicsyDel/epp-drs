Invoice ID #{$Invoice->ID}
Issued for: {$Invoice->Description} 
Amount: {$currency}{$Invoice->GetTotal()|number_format:2}