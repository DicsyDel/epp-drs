<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    <xsl:template match='//config/contacts/contact'>
        <xsl:variable name="groupname" select="@group" />   
        <xsl:choose>            
            <xsl:when test="//config/contact_groups/group[attribute::name = $groupname]">
                <!-- OK -->
            </xsl:when>
            <xsl:otherwise>
                <error>Group <xsl:value-of select="$groupname"/> is not defined in groups section</error>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    
    <xsl:template match='//config/contact_groups/group/display_fields/*/field'>
        <xsl:variable name="fieldname" select="@name"></xsl:variable>
        <xsl:choose>           
            <xsl:when test="../../../fields/field[@name = $fieldname] or ../../../extra_fields/if/field[@name=$fieldname]">
                <!-- OK -->
            </xsl:when>
            <xsl:otherwise>
                <error>Unknown field name "<xsl:value-of select="$fieldname"/>" in display_fields of contact_group <xsl:value-of select="../../../attribute::name"/></error>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
</xsl:stylesheet>